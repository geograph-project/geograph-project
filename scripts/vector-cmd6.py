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

# --- Configuration ---
PUT_BATCH_LIMIT = 500 # S3Vectors PutVectors API limit per call

# Initialize Bedrock and S3Vectors clients globally with the new region
bedrock = boto3.client("bedrock-runtime", region_name="us-east-1") # Changed region to us-east-1
s3vectors = boto3.client('s3vectors', region_name='us-east-1') # Changed region to us-east-1

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

def insert_data(vector_bucket_name: str, index_name: str, data_file: str):
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
            embedding = get_embedding(text)
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
    mysql_where: str = None
):
    """
    Connects to MySQL, fetches data from the specified table,
    converts binary embeddings back to float lists, and inserts them into S3Vectors with batching.
    """
    conn = None
    cursor = None
    try:
        print(f"Connecting to MySQL database '{mysql_db}' on '{mysql_host}'...")
        conn = mysql.connector.connect(
            host=mysql_host,
            user=mysql_user,
            password=mysql_password,
            database=mysql_db,

            # Use buffered=False for unbuffered cursor to process row by row
            # This prevents loading the entire result set into memory.
            # dictionary=True is still supported with unbuffered cursors.
            buffered=False
        )
        cursor = conn.cursor(dictionary=True) # Use dictionary=True to access columns by name

        # Parse selected columns to find aliases for 'id' and 'embeddings'
        parsed_select_cols = []
        id_col_alias = None
        embeddings_col_alias = None
        
        found_id_col = False
        found_embeddings_col = False

        for part in [col.strip() for col in mysql_select_cols.split(',')]:
            if ' AS ' in part.upper():
                original_name, alias_name = part.upper().split(' AS ')
                original_name = original_name.strip()
                alias_name = alias_name.strip()
                parsed_select_cols.append(part)
                
                if alias_name.lower() == 'id':
                    id_col_alias = 'id'
                    found_id_col = True
                elif alias_name.lower() == 'embeddings':
                    embeddings_col_alias = 'embeddings'
                    found_embeddings_col = True
            else:
                parsed_select_cols.append(part)
                if part.lower() == 'id':
                    id_col_alias = 'id'
                    found_id_col = True
                elif part.lower() == 'embeddings':
                    embeddings_col_alias = 'embeddings'
                    found_embeddings_col = True

        if not found_id_col:
            print("Error: A column aliased as 'id' or named 'id' must be included in --select option.")
            sys.exit(1)
        if not found_embeddings_col:
            print("Error: A column aliased as 'embeddings' or named 'embeddings' must be included in --select option.")
            sys.exit(1)

        query = f"SELECT {mysql_select_cols} FROM {mysql_table}"
        if mysql_where:
            query += f" WHERE {mysql_where}"
        
        print(f"Executing MySQL query: {query}")
        cursor.execute(query)

        all_vectors = []
        i = 0;
        rows_processed = 0
        total_inserted = 0
        for row in cursor:
            row_id = row.get("id")
            embeddings_bytes = row.get("embeddings")
            
            if row_id is None:
                print(f"Skipping row due to missing 'id' column in selected data: {row}")
                continue

            if embeddings_bytes is None:
                print(f"Skipping row ID {row_id}: 'embeddings' column is NULL.")
                continue

            try:
                # Convert bytes back to numpy array (assuming float32)
                embedding_np = np.frombuffer(embeddings_bytes, dtype=np.float32)
                # Ensure the embedding has the expected dimensions (e.g., 512)
                if embedding_np.shape[0] != 512:
                     print(f"Warning: Embedding for ID {row_id} has unexpected dimensions ({embedding_np.shape[0]}). Expected 512. Skipping.")
                     continue
                embedding_list = embedding_np.tolist()

                metadata = {}
                for col_name, col_value in row.items():
                    if col_name not in ['id', 'embeddings']:
                        if isinstance(col_value, Decimal):
                            metadata[col_name] = float(col_value)
                        else:
                            metadata[col_name] = col_value

                all_vectors.append({
                    "key": f"{row_id}",
                    "data": {"float32": embedding_list},
                    "metadata": metadata
                })
                rows_processed += 1

                ## actully should really just submit them as go
                if len(all_vectors) == PUT_BATCH_LIMIT:
                    s3vectors.put_vectors(
                        vectorBucketName=vector_bucket_name,
                        indexName=index_name,
                        vectors=all_vectors,
                    )

                    total_inserted += len(all_vectors)
                    print(f"  Batch {i + 1} successful. Inserted {len(all_vectors)} vectors.")
                    time.sleep(0.5) # Small delay to respect rate limits
                    all_vectors = []
                    i += 1

                if rows_processed % 1000 == 0:
                    print(f"  Processed {rows_processed} rows from MySQL...")

            except Exception as e:
                print(f"Error processing row ID {row_id} from MySQL: {e}")
                continue

        if not all_vectors:
            print("No rows left to insert.")
            return

        #this can cope with large results (doing the batching, but if submitted as go, not needed, so will always be just one batch anyway!

        num_vectors = len(all_vectors)
        num_batches = math.ceil(num_vectors / PUT_BATCH_LIMIT)

        print(f"Attempting to insert {num_vectors} vectors from MySQL in {num_batches} batches (batch size: {PUT_BATCH_LIMIT})...")

        for i in range(num_batches):
            start_index = i * PUT_BATCH_LIMIT
            end_index = min((i + 1) * PUT_BATCH_LIMIT, num_vectors)
            current_batch = all_vectors[start_index:end_index]

            print(f"  Processing batch {i + 1}/{num_batches} (vectors {start_index} to {end_index - 1})...")
            try:
                s3vectors.put_vectors(
                    vectorBucketName=vector_bucket_name,
                    indexName=index_name,
                    vectors=current_batch,
                )
                total_inserted += len(current_batch)
                print(f"  Batch {i + 1} successful. Inserted {len(current_batch)} vectors.")
                time.sleep(0.5) # Small delay to respect rate limits
            except Exception as e:
                print(f"  Error inserting batch {i + 1} into S3Vectors: {e}")
                print("  Attempting to continue with next batch...")
                continue # Continue to next batch even if one fails

        print(f"Successfully inserted {total_inserted} vectors from MySQL into '{index_name}' in '{vector_bucket_name}'.")

    except mysql.connector.Error as err:
        print(f"MySQL Error: {err}")
        sys.exit(1)
    except Exception as e:
        print(f"An unexpected error occurred during MySQL insertion: {e}")
        sys.exit(1)
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()
            print("MySQL connection closed.")


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



def run_query(vector_bucket_name: str, index_name: str, query_text: str, top_k: int, query_filter: str = None):
    """
    Runs a similarity query against the S3Vectors index.
    Accepts a text query, top_k results, and an optional JSON filter string.
    """
    print(f"Generating embedding for query: '{query_text}'...")
    try:
        query_embedding = get_embedding(query_text) # get_embedding now includes dimensions
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
        default="id, label, embeddings", # Default columns
        help="Comma-separated list of columns to fetch. 'id' and 'embeddings' are required. "
             "Other columns will be added as metadata. (default: 'id, label, embeddings')"
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

    if args.command == "insert-file":
        insert_data(args.bucket, args.index, args.file)
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
            args.where
        )
    elif args.command == "query":
        run_query(args.bucket, args.index, args.text, args.top_k, args.filter)
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
