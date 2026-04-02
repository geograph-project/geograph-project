import argparse
import requests
import base64
import json
import os

BASE_URL = os.environ.get("CONF_EMBED_API", "http://python-embed16.dev.svc.cluster.local:8000")


def test_text_endpoint(input_text: str, model_alias: str):
    """Tests the /text endpoint with a given string and model alias."""
    print(f"Testing {BASE_URL}/text endpoint with model '{model_alias}'...")
    payload = {
        "text": input_text,
        "model": model_alias
    }
    response = requests.post(f"{BASE_URL}/text", json=payload)
    handle_response(response)

def test_generate_endpoint(input_text: str, model_alias: str):
    """Tests the /generate endpoint with a given string and model alias."""
    print(f"Testing {BASE_URL}/generate endpoint with model '{model_alias}'...")
    payload = {
        "text": input_text,
        "model": model_alias
    }
    response = requests.post(f"{BASE_URL}/generate", json=payload)
    handle_response(response)

def test_image_endpoint(image_path: str, model_alias: str):
    """Tests the /image endpoint with a local image file and model alias."""
    print(f"Testing {BASE_URL}/image endpoint with model '{model_alias}'...")
    if not os.path.exists(image_path):
        print(f"Error: Image file not found at '{image_path}'")
        return

    try:
        with open(image_path, "rb") as image_file:
            encoded_string = base64.b64encode(image_file.read()).decode('utf-8')
    except IOError:
        print(f"Error: Could not read image file at '{image_path}'")
        return

    payload = {
        "image": encoded_string,
        "model": model_alias
    }
    response = requests.post(f"{BASE_URL}/image", json=payload)
    handle_response(response)

def test_caption_endpoint(image_path: str, max_length: int):
    """Tests the /caption endpoint with a local image file."""
    print(f"Testing {BASE_URL}/caption endpoint...")
    if not os.path.exists(image_path):
        print(f"Error: Image file not found at '{image_path}'")
        return

    try:
        with open(image_path, "rb") as image_file:
            encoded_string = base64.b64encode(image_file.read()).decode('utf-8')
    except IOError:
        print(f"Error: Could not read image file at '{image_path}'")
        return

    # The caption endpoint uses a hardcoded model, so we don't pass an alias.
    payload = {
        "image": encoded_string,
        "max_length": max_length
    }
    try:
        response = requests.post(f"{BASE_URL}/caption", json=payload)
        response.raise_for_status()
        caption = response.json()
        print(f"Caption (max_length={max_length}): {caption}")
    except requests.exceptions.RequestException as e:
        print(f"Error testing caption endpoint: {e}")

def extract_json_from_response(text):
    """
    Extracts and parses a JSON object from a string enclosed in a markdown code block.
    Returns the parsed JSON object if found, otherwise returns None.
    """
    try:
        start_marker = "```json"
        end_marker = "```"

        if start_marker in text and end_marker in text:
            start_index = text.find(start_marker) + len(start_marker)
            end_index = text.rfind(end_marker)

            # Extract the raw JSON string and clean up whitespace
            json_string = text[start_index:end_index].strip()

            # Load the JSON string into a Python object
            return json.loads(json_string)
    except (json.JSONDecodeError, ValueError):
        # Return None if the JSON is invalid or extraction fails
        return None
    return None

def handle_response(response):
    """Prints the response from the API or an error message."""
    print("-" * 50)
    print(f"Status Code: {response.status_code}")
    try:
        if response.status_code == 200:
            data = response.json()
            if isinstance(data, list) and all(isinstance(x, (int, float)) for x in data):
                vector = data
                count = len(vector)
                first_10 = vector[:10]
                last_value = vector[-1]

                print("Response Body:")
                print(f"Vector Count: {count}")
                print(f"First 10 values: {first_10}")
                print(f"Last value: {last_value}")

            elif 'response' in data:
                # Attempt to extract JSON from the LLM's response text
                extracted_json = extract_json_from_response(data['response'])

                if extracted_json is not None:
                    print("Extracted and Parsed LLM JSON:")
                    print(json.dumps(extracted_json, indent=2))
                else:
                    # Fallback to a generic print if no valid JSON is extracted
                    print("Response Body:")
                    print(json.dumps(data, indent=2))
            else:
                print("Response Body:")
                print(json.dumps(data, indent=2))
        else:
            print("Error Response:")
            print(response.text)
    except json.JSONDecodeError:
        print("Raw Response:")
        print(response.text)
    print("-" * 50)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Test FastAPI endpoints for text and image processing.")

    parser.add_argument("endpoint", choices=["text", "generate", "image", "caption"], help="The endpoint to test.")
    parser.add_argument("input", help="Input text or image file path.")
    parser.add_argument("--model", help="Optional: Model alias to use.", default='clip')

    # Add an optional argument for max_length with a default value
    parser.add_argument("--max_length", type=int, default=50, help="Optional: Specify the max_length for the captioning model.")

    args = parser.parse_args()

    # Determine which endpoint to test
    if args.endpoint == "text":
        if not args.model:
            print("Error: The 'text' endpoint requires a --model alias.")
        else:
            test_text_endpoint(args.input, args.model)
    elif args.endpoint == "generate":
        if not args.model:
            print("Error: The 'generate' endpoint requires a --model alias.")
        else:
            test_generate_endpoint(args.input, args.model)

    elif args.endpoint == "image":
        if not args.model:
            print("Error: The 'image' endpoint requires a --model alias.")
        else:
            test_image_endpoint(args.input, args.model)
    elif args.endpoint == "caption":
        test_caption_endpoint(args.input, args.max_length)

    ##todo multimodal-genrate (needs text+image!
