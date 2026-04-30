import boto3
import json
import argparse
import sys
import mysql.connector
import numpy as np
import os # Import the os module to access environment variables
from decimal import Decimal # Import Decimal type
import math # Import math for ceil
import time # Import time for sleep
import requests
import json
import datetime

# --- Configuration ---
PUT_BATCH_LIMIT = 500 # S3Vectors PutVectors API limit per call

# Our local encoding API - should be detected from CONF
api_url = os.environ.get("CONF_EMBED_API", "http://python-embed16.dev.svc.cluster.local:8000") + "/text"

# Initialize Bedrock and S3Vectors clients globally with the new region
bedrock = boto3.client("bedrock-runtime", region_name="eu-west-1") # this is available in local region
s3vectors = boto3.client('s3vectors', region_name='us-east-1') # s3vectors launched in preview in US region, so most of our indexes are still there


def get_embedding(text: str) -> list[float]:
    """
    Generates an embedding for the given text using Amazon Titan Text Embeddings V2,
    specifying dimensions.
    """
    body = json.dumps({
        "inputText": text,
        "dimensions": 512 # Added dimensions parameter
    })
    response = bedrock.invoke_model(
        modelId='amazon.titan-embed-text-v2:0',
        body=body
    )
    response_body = json.loads(response['body'].read())
    return response_body['embedding']

def get_text_embeddings(input_text, model = "clip"):
    """
    Connects to an API to get text embeddings.

    Args:
        input_text (str): The text for which to get embeddings.
        model (str): The model to use, eg clip (default), bgesmall

    Returns:
        dict or None: The JSON response from the API as a dictionary, or None if an error occurs.
    """

    # The data to send in the request body as a JSON string
    post_data = {'text': input_text, 'model': model}

    try:
        # Make the POST request
        response = requests.post(api_url, json=post_data)

        # Raise an HTTPError for bad responses (4xx or 5xx)
        response.raise_for_status()

        # Decode the JSON response
        return response.json()

    except requests.exceptions.HTTPError as errh:
        print(f"Http Error: {errh}")
        return None
    except requests.exceptions.ConnectionError as errc:
        print(f"Error Connecting: {errc}")
        return None
    except requests.exceptions.Timeout as errt:
        print(f"Timeout Error: {errt}")
        return None
    except requests.exceptions.RequestException as err:
        print(f"Something went wrong: {err}")
        return None
    except json.JSONDecodeError as errj:
        print(f"JSON Decode Error: {errj}")
        return None

def insert_data(vector_bucket_name: str, index_name: str, data_file: str, model: str):
    """
    Inserts data from a JSON file into the S3Vectors index, with batching.
    Data is expected to be a JSON file containing a list of objects,
    each with 'id', 'text', and optionally 'genre'.
    """
    try:
        with open(data_file, 'r') as f:
            items_to_insert = json.load(f)
    except FileNotFoundError:
        print(f"Error: Data file '{data_file}' not found.")
        sys.exit(1)
    except json.JSONDecodeError:
        print(f"Error: Could not decode JSON from '{data_file}'. Ensure it's valid JSON.")
        sys.exit(1)

    vectors_for_s3 = []
    print(f"Generating embeddings for {len(items_to_insert)} items and preparing for insertion...")
    for item in items_to_insert:
        item_id = item.get("id")
        text = item.get("text")
        genre = item.get("genre")

        if not item_id or not text:
            print(f"Skipping item due to missing 'id' or 'text': {item}")
            continue

        try:
            embedding = None
            if model == "titan":
                embedding = get_embedding(text)
            else:
                embedding = get_text_embeddings(text, model)

            if embedding is None:
                print(f"Skipping item ID {item_id} due to embedding generation failure.")
                continue

            metadata = {"id": item_id, "source_text": text}
            if genre:
                metadata["genre"] = genre

            vectors_for_s3.append({
                "key": str(item_id), # Ensure key is string
                "data": {"float32": embedding},
                "metadata": metadata
            })
            print(f"  Generated embedding for ID: {item_id}")
        except Exception as e:
            print(f"Error generating embedding for item ID {item_id}: {e}")
            continue

    if not vectors_for_s3:
        print("No valid items to insert after processing.")
        return

    num_vectors = len(vectors_for_s3)
    num_batches = math.ceil(num_vectors / PUT_BATCH_LIMIT)
    total_inserted = 0

    print(f"Attempting to insert {num_vectors} vectors in {num_batches} batches (batch size: {PUT_BATCH_LIMIT})...")

    for i in range(num_batches):
        start_index = i * PUT_BATCH_LIMIT
        end_index = min((i + 1) * PUT_BATCH_LIMIT, num_vectors)
        current_batch = vectors_for_s3[start_index:end_index]

        print(f"  Processing batch {i + 1}/{num_batches} (vectors {start_index} to {end_index - 1})...")

        try:
            s3vectors.put_vectors(
                vectorBucketName=vector_bucket_name,
                indexName=index_name,
                vectors=current_batch,
            )
            total_inserted += len(current_batch)
            print(f"  Batch {i + 1} successful. Inserted {len(current_batch)} vectors.")
            time.sleep(0.2) # Small delay to respect rate limits
        except Exception as e:
            print(f"  Error inserting batch {i + 1} into S3Vectors: {e}")
            # Decide whether to continue or break on error
            print("  Attempting to continue with next batch...")
            continue # Continue to next batch even if one fails

    print(f"Successfully inserted {total_inserted} vectors into '{index_name}' in '{vector_bucket_name}'.")


def insert_from_mysql(
    vector_bucket_name: str,
    index_name: str,
    mysql_host: str,
    mysql_user: str,
    mysql_password: str,
    mysql_db: str,
    mysql_table: str,
    mysql_select_cols: str,
    model: str,
    mysql_where: str = None
):
    """
    Connects to MySQL, fetches data in batches, and inserts it into S3Vectors.
    Supports Hybrid Mode: uses existing 'embeddings', otherwise generates from 
    'input_text' and backfills the DB.
    """
    # --- Column Parsing and Mode Detection ---
    parsed_select_cols = [col.strip() for col in mysql_select_cols.split(',')]

    found_id_col = any(part.lower().endswith('as id') or part.lower() == 'id' for part in parsed_select_cols)
    found_input_text_col = any(part.lower().endswith('as input_text') or part.lower() == 'input_text' for part in parsed_select_cols)
    found_embeddings_col = any(part.lower().endswith('as embeddings') or part.lower() == 'embeddings' for part in parsed_select_cols)

    if not found_id_col:
        print("Error: A column aliased as 'id' or named 'id' must be included in the --select option.")
        sys.exit(1)

    # --- New Logic: Hybrid Mode Detection ---
    generate_embeddings_only_mode = found_input_text_col and not found_embeddings_col
    use_precomputed_embeddings_only_mode = found_embeddings_col and not found_input_text_col
    hybrid_mode = found_input_text_col and found_embeddings_col

    if not (generate_embeddings_only_mode or use_precomputed_embeddings_only_mode or hybrid_mode):
        print("Error: You must include either a column named/aliased as 'input_text' (for embedding generation), 'embeddings' (for pre-computed vectors), or BOTH for hybrid mode, in the --select option.")
        sys.exit(1)

    # Simplified status printout
    if hybrid_mode:
        print("Mode: **Hybrid Mode** (Will use existing 'embeddings' if present, otherwise generate from 'input_text' and backfill DB).")
    elif generate_embeddings_only_mode:
        print("Mode: **Generate Embeddings Only** (Using 'input_text').")
    else: # use_precomputed_embeddings_only_mode
        print("Mode: **Pre-computed Embeddings Only** (Using 'embeddings').")

    id_column_name = 'id'
    for part in parsed_select_cols:
        if ' as id' in part.lower():
            id_column_name = part.lower().split(' as id')[0].strip()
            break

    # We need the actual column name for the 'embeddings' column for the backfill query.
    embeddings_column_name = 'embeddings'
    if found_embeddings_col:
        for part in parsed_select_cols:
            if ' as embeddings' in part.lower():
                embeddings_column_name = part.lower().split(' as embeddings')[0].strip()
                break

    ##########################################

    def get_db_connection():
        """
        Nests a function to establish or re-establish a database connection.
        """
        nonlocal conn, cursor
        try:
            print(f"Connecting to MySQL database '{mysql_db}' on '{mysql_host}'...")
            conn = mysql.connector.connect(
                host=mysql_host,
                user=mysql_user,
                password=mysql_password,
                database=mysql_db
            )
            cursor = conn.cursor(dictionary=True)
            return conn, cursor
        except mysql.connector.Error as err:
            print(f"Error connecting to database: {err}")
            return None, None

    def update_embedding_in_db(row_id: int, embedding_bytes: bytes):
        """
        Updates the embeddings column for a given row_id.
        Requires a working connection/cursor.
        """
        nonlocal conn, cursor # Use the outer conn/cursor
        if not conn or not conn.is_connected():
            print(f"Warning: Cannot backfill DB for ID {row_id}. DB connection is not active.")
            return

        # Use the dynamically determined column names (id_column_name and embeddings_column_name)
        update_query = (
            f"UPDATE {mysql_table} SET {embeddings_column_name} = %s WHERE {id_column_name} = %s"
        )
        try:
            # The bytearray/bytes object is passed directly to the query parameter %s
            cursor.execute(update_query, (embedding_bytes, row_id))
            conn.commit()
        except mysql.connector.Error as err:
            print(f"Error backfilling embedding for ID {row_id}: {err}")
        except Exception as e:
            print(f"Unexpected error during backfill for ID {row_id}: {e}")

    conn, cursor = get_db_connection()

    ##########################################

    batch_size = PUT_BATCH_LIMIT
    last_id = 0
    total_inserted = 0

    while True:
        try:
            print(f"\n--- Processing new batch from id > {last_id} with {model} ---")

            # Check if connection is valid; reconnect if not.
            if conn is None or not conn.is_connected():
                print("Connection lost. Attempting to reconnect...")
                conn, cursor = get_db_connection()
                if conn is None:
                    print("Reconnection failed. Waiting before retrying...")
                    time.sleep(30)
                    continue

            base_query = f"SELECT {mysql_select_cols} FROM {mysql_table}"

            where_clause = f"WHERE {id_column_name} > {last_id}"
            if mysql_where:
                where_clause += f" AND ({mysql_where})"

            paginated_query = f"SELECT {mysql_select_cols} FROM {mysql_table} {where_clause} ORDER BY {id_column_name} ASC LIMIT {batch_size}"

            print(f"Executing MySQL batch query: {paginated_query}")
            cursor.execute(paginated_query)

            rows = cursor.fetchall()
            if not rows:
                print("No more rows to process from MySQL. Finishing.")
                break

            print("Beginning Processing...")

            # Preview the first row, if it's the first batch
            if not last_id and rows:
                preview_row = dict(rows[0])

                # Truncate the 'embeddings' column if it exists and is a bytearray
                if 'embeddings' in preview_row and isinstance(preview_row['embeddings'], bytearray):
                    truncated_bytes = preview_row['embeddings'][:24]
                    preview_row['embeddings'] = f"{truncated_bytes!r} ... (truncated)"

                print("Example:", preview_row)

            all_vectors = []
            counter = 0;
            for row in rows:
                row_id = row.get("id")
                if row_id is None:
                    print(f"Skipping row due to missing 'id' column: {row}")
                    continue

                last_id = row_id

                try:
                    embedding_np = None
                    metadata = {}

                    if counter % 10 == 0:
                         print(f"Processing item {counter}...", end="\r", flush=True)

                    embeddings_bytes = row.get("embeddings")

                    # Case 1: Pre-computed embeddings EXIST and we selected the column
                    if embeddings_bytes and found_embeddings_col:
                        embedding_np = np.frombuffer(embeddings_bytes, dtype=np.float32)

                    # Case 2: Embeddings DO NOT EXIST (or we are in Generate-Only mode)
                    elif (hybrid_mode or generate_embeddings_only_mode):
                        input_text = row.get("input_text")
                        if input_text is None:
                            print(f"Skipping row ID {row_id}: 'input_text' column is NULL.")
                            continue

                        embedding = get_embedding(input_text) if model == 'titan' else get_text_embeddings(input_text, model)
                        if not embedding:
                            print(f"Skipping row ID {row_id} due to embedding generation failure.")
                            continue

                        # Convert list to NumPy array for standardization (float32)
                        embedding_np = np.array(embedding, dtype=np.float32)

                        if hybrid_mode:
                            update_embedding_in_db(row_id, embedding_np.tobytes())

                    else:
                        print(f"Skipping row ID {row_id}: 'embeddings' column is NULL and 'input_text' is not available for generation.")
                        continue

                    for col_name, col_value in row.items():
                        if col_name.lower() not in ['id', 'embeddings', 'input_text']:
                            if col_value is None and col_name == 'images': metadata[col_name] = 0
                            elif col_value is not None:
                                if isinstance(col_value, Decimal): metadata[col_name] = float(col_value)
                                elif isinstance(col_value, datetime.date): metadata[col_name] = col_value.strftime("%Y-%m-%d")
                                elif isinstance(col_value, bytearray): metadata[col_name] = col_value.decode('utf-8')
                                else: metadata[col_name] = col_value

                    if embedding_np is not None and embedding_np.size > 0:
                        all_vectors.append({
                            "key": str(row_id),
                            "data": {"float32": embedding_np.tolist()},
                            "metadata": metadata
                        })

                    counter += 1

                except Exception as e:
                    print(f"Error processing row ID {row_id}: {e}")
                    continue

            if all_vectors:
                print(f"Inserting {len(all_vectors)} vectors into S3Vectors...")
                s3vectors.put_vectors(
                    vectorBucketName=vector_bucket_name,
                    indexName=index_name,
                    vectors=all_vectors,
                )
                total_inserted += len(all_vectors)
                print(f"Successfully inserted batch. Total inserted so far: {total_inserted}")

            # Check if the number of rows is less than the batch size
            # This indicates that we've processed the final chunk of data.
            if len(rows) < batch_size:
                print(f"  .... Final batch processed with {len(rows)} rows. Breaking loop.")
                break

        except mysql.connector.Error as err:
            print(f"MySQL Error: {err}. Retrying in 10 seconds...")
            # Close the connection and set to None to force a reconnect on the next iteration.
            if conn and conn.is_connected():
                conn.close()
            conn = None
            cursor = None
            time.sleep(10)
            continue # Retry the batch
        except Exception as e:
            print(f"An unexpected error occurred: {e}")
            break # Exit on other errors

    # Final cleanup
    if cursor:
        cursor.close()
    if conn:
        conn.close()

    print(f"\nSuccessfully inserted a total of {total_inserted} vectors from MySQL into '{index_name}' in '{vector_bucket_name}'.")


def delete_vectors_by_keys(vector_bucket_name: str, index_name: str, keys: list[str]):
    """
    Deletes specific vectors from the S3Vectors index by their keys.
    """
    if not keys:
        print("No keys provided for deletion.")
        return

    print(f"Attempting to delete {len(keys)} vectors from '{index_name}' in '{vector_bucket_name}'...")
    try:
        response = s3vectors.delete_vectors(
            vectorBucketName=vector_bucket_name,
            indexName=index_name,
            keys=keys
        )
        print(f"Deletion successful. Status: {response.get('ResponseMetadata', {}).get('HTTPStatusCode')}")
    except Exception as e:
        print(f"Error deleting vectors: {e}")
        sys.exit(1)

def truncate_index(vector_bucket_name: str, index_name: str):
    """
    Deletes all vectors from an S3Vectors index by listing all keys and deleting them in batches.
    """
    print(f"Truncating index '{index_name}' in '{vector_bucket_name}'...")
    all_keys_to_delete = []
    next_token = None
    total_deleted = 0
    
    # Max results for list_vectors is 1000
    list_batch_size = 1000
    delete_batch_size = 500

    while True:
        try:
            list_params = {
                "vectorBucketName": vector_bucket_name,
                "indexName": index_name,
                "maxResults": list_batch_size,
                "returnData": False, # Only need keys
                "returnMetadata": False # Only need keys
            }
            if next_token:
                list_params["nextToken"] = next_token

            print(f"  Listing vectors (batch size {list_batch_size})...")
            response = s3vectors.list_vectors(**list_params)
            
            keys_in_batch = [vector.get('key') for vector in response.get('vectors', []) if vector.get('key')]
            all_keys_to_delete.extend(keys_in_batch)
            
            print(f"  Found {len(keys_in_batch)} keys in current batch. Total found: {len(all_keys_to_delete)}")

            next_token = response.get('nextToken')
            if not next_token:
                break # No more pages

            # Add a small delay to avoid hitting API rate limits
            time.sleep(0.5) 

        except Exception as e:
            print(f"Error listing vectors for truncation: {e}")
            sys.exit(1)

    if not all_keys_to_delete:
        print(f"No vectors found in index '{index_name}' to truncate.")
        return

    print(f"Total {len(all_keys_to_delete)} vectors found. Starting deletion in batches...")
    
    # Delete in batches
    for i in range(0, len(all_keys_to_delete), delete_batch_size):
        batch_to_delete = all_keys_to_delete[i:i + delete_batch_size]
        try:
            print(f"  Deleting batch {i // delete_batch_size + 1} of {len(batch_to_delete)} vectors...")
            s3vectors.delete_vectors(
                vectorBucketName=vector_bucket_name,
                indexName=index_name,
                keys=batch_to_delete
            )
            total_deleted += len(batch_to_delete)
            print(f"  Successfully deleted {len(batch_to_delete)} vectors. Total deleted: {total_deleted}")
            time.sleep(0.2) # Small delay between delete batches

        except Exception as e:
            print(f"Error deleting batch of vectors (keys {batch_to_delete[0]} to {batch_to_delete[-1]}): {e}")
            # Decide whether to continue or exit on error
            # For truncation, we might want to continue best effort
            print("  Attempting to continue with next batch...")
            continue 

    print(f"\nSuccessfully truncated index '{index_name}'. Deleted a total of {total_deleted} vectors.")


def run_get_embedding(text: str, model: str):
    """
    Gets the embedding for a given text and prints it as JSON.
    """
    #print(f"Generating embedding for text: '{text}' using model: {model}...")
    try:
        embedding = None
        if model == "titan":
            embedding = get_embedding(text)
        else:
            embedding = get_text_embeddings(text, model)

        if embedding is None:
            print("Error: Could not generate embedding for the text.", file=sys.stderr)
            sys.exit(1)

        print(json.dumps(embedding))

    except Exception as e:
        print(f"Error generating embedding: {e}", file=sys.stderr)
        sys.exit(1)


def run_query(vector_bucket_name: str, index_name: str, query_text: str, top_k: int, model: str, query_filter: str = None):
    """
    Runs a similarity query against the S3Vectors index.
    Accepts a text query, top_k results, and an optional JSON filter string.
    """
    print(f"Generating embedding for query: '{query_text}' using model: {model}...")
    try:
        query_embedding = None
        if model == "titan":
            query_embedding = get_embedding(query_text)
        else:
            query_embedding = get_text_embeddings(query_text, model)

        if query_embedding is None:
            print("Error: Could not generate embedding for the query.")
            sys.exit(1)

    except Exception as e:
        print(f"Error generating embedding for query: {e}")
        sys.exit(1)
    
    filter_dict = None
    if query_filter:
        try:
            filter_dict = json.loads(query_filter)
        except json.JSONDecodeError:
            print(f"Error: Invalid JSON for filter: '{query_filter}'.")
            sys.exit(1)

    print(f"Running query on '{index_name}' with top_k={top_k} and filter={filter_dict}...")
    try:
        query_params = {
            "vectorBucketName": vector_bucket_name,
            "indexName": index_name,
            "queryVector": {"float32": query_embedding},
            "topK": top_k,
            "returnDistance": True,
            "returnMetadata": True
        }
        if filter_dict:
            query_params["filter"] = filter_dict

        query_response = s3vectors.query_vectors(**query_params)
        results = query_response["vectors"]
        
        if results:
            print("\nQuery Results:")
            for i, result in enumerate(results):
                print(f"--- Result {i+1} ---")
                print(f"  Key: {result.get('key')}")
                print(f"  Distance: {result.get('distance')}")
                if 'metadata' in result:
                    print("  Metadata:")
                    for k, v in result['metadata'].items():
                        print(f"    {k}: {v}")
                print("-" * 20)
        else:
            print("No results found for your query.")

    except Exception as e:
        print(f"Error running query against S3Vectors: {e}")
        sys.exit(1)

def main():
    global s3vectors

    parser = argparse.ArgumentParser(
        description="A command-line tool for managing vector embeddings with AWS Bedrock and S3Vectors."
    )
    # Common arguments
    parser.add_argument(
        "-b", "--bucket",
        default= "geograph-vector-bucket",
        help="The name of the S3Vectors vector bucket."
    )
    parser.add_argument(
        "-i", "--index",
        default= "test-index",
        help="The name of the S3Vectors index within the bucket."
    )
    parser.add_argument(
        "-m", "--model",
        default="clip",
        choices=["clip", "pe", "titan", "mpnet", "minilm", "bgesmall"],
        help="The embedding model to use: 'clip' (default) or 'titan'."
    )

    subparsers = parser.add_subparsers(dest="command", help="Available commands")

    # Subparser for inserting data from JSON file
    insert_file_parser = subparsers.add_parser("insert-file", help="Insert data from a JSON file into the S3Vectors index.")
    insert_file_parser.add_argument(
        "-f", "--file",
        required=True,
        help="Path to a JSON file containing the data to insert. "
             "Each object must have 'id' and 'text', and can optionally have 'genre'."
    )

    # Subparser for inserting data from MySQL
    insert_mysql_parser = subparsers.add_parser("insert-mysql", help="Insert data from a MySQL table into the S3Vectors index.")

    # MySQL connection details defaulting to environment variables
    insert_mysql_parser.add_argument(
        "-H", "--host",
        default=os.environ.get("CONF_DB_CONNECT"), # Default from env var
        help="MySQL database host (defaults to CONF_DB_CONNECT environment variable)."
    )
    insert_mysql_parser.add_argument(
        "-u", "--user",
        default=os.environ.get("CONF_DB_USER"), # Default from env var
        help="MySQL database user (defaults to CONF_DB_USER environment variable)."
    )
    insert_mysql_parser.add_argument(
        "-p", "--password",
        default=os.environ.get("CONF_DB_PWD"), # Default from env var
        help="MySQL database password (defaults to CONF_DB_PWD environment variable)."
    )
    insert_mysql_parser.add_argument(
        "-D", "--db",
        default=os.environ.get("CONF_DB_DB"), # Default from env var
        help="MySQL database name (defaults to CONF_DB_DB environment variable)."
    )

    insert_mysql_parser.add_argument("-t", "--table", required=True, help="MySQL table name containing embeddings.")

    insert_mysql_parser.add_argument(
        "-s", "--select",
        required=True,
        help="Comma-separated list of columns to fetch. Must include a column named or aliased as 'id'. "
             "For embedding generation, include a column named/aliased as 'input_text'. "
             "Alternatively, for pre-computed vectors, include a column named/aliased as 'embeddings'. "
             "Other columns are treated as metadata."
    )
    insert_mysql_parser.add_argument("-w", "--where", help="Optional WHERE clause for the MySQL query (e.g., 'genre=\"scifi\"').")


    # Subparser for deleting vectors by key
    delete_keys_parser = subparsers.add_parser("delete-keys", help="Delete specific vectors by key from the S3Vectors index.")
    delete_keys_parser.add_argument(
        "-K", "--keys",
        nargs='+', # Accepts one or more arguments
        required=True,
        help="Space-separated list of vector keys to delete (e.g., key1 key2 mysql_123)."
    )

    # Subparser for truncating an index
    truncate_parser = subparsers.add_parser("truncate", help="Delete ALL vectors from an S3Vectors index.")
    truncate_parser.add_argument(
        "--confirm-truncate",
        action="store_true", # This argument doesn't take a value, just its presence is True
        help="Required to confirm truncation. This action deletes all vectors and is irreversible."
    )

    # Subparser for getting an embedding
    get_embedding_parser = subparsers.add_parser("get-embedding", help="Get the embedding for a given text.")
    get_embedding_parser.add_argument(
        "-t", "--text",
        required=True,
        help="The text to get the embedding for."
    )

    # Subparser for running queries
    query_parser = subparsers.add_parser("query", help="Run a search query against the S3Vectors index.")
    query_parser.add_argument(
        "-q", "--text",
        required=True,
        help="The text query to search for."
    )
    query_parser.add_argument(
        "-k", "--top-k",
        type=int,
        default=3,
        help="The number of top similar results to return (default: 3)."
    )
    query_parser.add_argument(
        "-F", "--filter",
        help="Optional JSON string for filtering results (e.g., '{\"genre\":\"scifi\"}')."
    )

    args = parser.parse_args()

    if args.index == 'image-pe' or args.index == 'tags-bgesmall' or args.index == 'snippet-bgesmall':
        print("Switching to eu-west-1")
        s3vectors = boto3.client('s3vectors', region_name='eu-west-1') # s3vector can be tested in eu-west-1 now! (we've started putting some indexes there)

    if args.command == "insert-file":
        insert_data(args.bucket, args.index, args.file, args.model)
    elif args.command == "insert-mysql":
        insert_from_mysql(
            args.bucket,
            args.index,
            args.host,
            args.user,
            args.password,
            args.db,
            args.table,
            args.select,
            args.model,
            args.where
        )
    elif args.command == "query":
        run_query(args.bucket, args.index, args.text, args.top_k, args.model, args.filter)
    elif args.command == "get-embedding":
        run_get_embedding(args.text, args.model)
    elif args.command == "delete-keys":
        delete_vectors_by_keys(args.bucket, args.index, args.keys)
    elif args.command == "truncate":
        if not args.confirm_truncate:
            print("\nError: Truncating an index deletes ALL its vectors irreversibly.")
            print("To proceed, you must add the --confirm-truncate flag to your command.")
            sys.exit(1)
        truncate_index(args.bucket, args.index)
    else:
        parser.print_help()
        if not args.command:
            print("\nError: Please specify a command ('insert-file', 'insert-mysql', or 'query').")

if __name__ == "__main__":
    main()
