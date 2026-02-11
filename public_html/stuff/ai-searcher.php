<?

require_once('geograph/global.inc.php');

/*

curl \
  -H 'Content-Type: application/json' \
  -d '{"contents":[{"parts":[{"text":"Explain how AI works"}]}]}' \
  -X POST 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=YOUR_API_KEY'


ubuntu:~/ $ curl   -H 'Content-Type: application/json'   -d '{"contents":[{"parts":[{"text":"Explain how AI works"}]}]}'   -X POST 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=.....'
{
  "candidates": [
    {
      "content": {
        "parts": [
          {
            "text": "## Demystifying AI: A Simplified Explanation\n\nArtificial Intelligence (AI) is essentially the ability of machines to perform tasks that typically require human intelligence. This intelligence is achieved through various techniques, primarily:\n\n**1. Machine Learning (ML):** This is the core of modern AI. ML algorithms \"learn\" from data without explicit programming. Think of it as teaching a machine to recognize patterns by showing it tons of examples. \n\n* **Supervised Learning:** The algorithm is trained on labelled data, where the machine learns to predict an output based on the input. (e.g., classifying images of cats and dogs based on labelled datasets)\n* **Unsupervised Learning:** The algorithm explores unlabelled data to discover hidden patterns and structures. (e.g., grouping customers based on their purchasing behavior)\n* **Reinforcement Learning:** The algorithm learns through trial and error, receiving rewards for good actions and penalties for bad ones. (e.g., training a robot to navigate a maze)\n\n**2. Deep Learning (DL):** A powerful subset of ML that uses artificial neural networks (ANNs) with many layers. These networks are inspired by the structure of the human brain and can handle complex tasks like image and speech recognition.\n\n**3. Natural Language Processing (NLP):** This branch of AI allows computers to understand and process human language. It helps machines interpret text, translate languages, and generate human-like text.\n\n**4. Computer Vision:** This field enables machines to \"see\" and interpret images and videos. It's used in applications like facial recognition, self-driving cars, and medical diagnosis.\n\n**How AI works in practice:**\n\n1. **Data collection and preparation:** Gathering relevant data is crucial for training AI models. This data needs to be cleaned, organized, and formatted for optimal learning.\n2. **Model training:** The selected algorithm is trained on the prepared data, adjusting its parameters to learn the desired patterns and relationships.\n3. **Model evaluation:** The trained model is tested on new data to assess its accuracy and performance.\n4. **Deployment and refinement:** Once deemed successful, the model is deployed for real-world applications and continuously monitored and refined for better performance.\n\n**Examples of AI in Action:**\n\n* **Self-driving cars:** Using computer vision and machine learning to navigate roads and avoid obstacles.\n* **Virtual assistants (Siri, Alexa):** Employing NLP to understand and respond to voice commands.\n* **Recommendation systems (Netflix, Amazon):** Using ML to predict user preferences and suggest relevant content or products.\n* **Medical diagnosis:** AI assists doctors in analyzing medical images and identifying potential health issues.\n\n**Remember:** AI is still a rapidly evolving field. While there are countless impressive applications, it's important to understand the limitations and ethical implications associated with its use. \n"
          }
        ],
        "role": "model"
      },
      "finishReason": "STOP",
      "index": 0,
      "safetyRatings": [
        {
          "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
          "probability": "NEGLIGIBLE"
        },
        {
          "category": "HARM_CATEGORY_HATE_SPEECH",
          "probability": "NEGLIGIBLE"
        },
        {
          "category": "HARM_CATEGORY_HARASSMENT",
          "probability": "NEGLIGIBLE"
        },
        {
          "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
          "probability": "NEGLIGIBLE"
        }
      ]
    }
  ],
  "usageMetadata": {
    "promptTokenCount": 4,
    "candidatesTokenCount": 585,
    "totalTokenCount": 589
  }
}

*/

//$API_KEY = $CONF['gemini_key']

if (!empty($_POST['input'])) {
	//{"contents":[{"parts":[{"text":"Explain how AI works"}]}]}
	/*{
		"contents":[
			{"parts":[
				{"text":"Explain how AI works"}
			]}
		]
	}*/
	$request = array(
		'contents'=>array(
			array(
				'parts'=>array(
					array('text'=> $_POST['input']),
				)
			)
		),
	);
	//print json_encode($request);
	$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=".$CONF['gemini_key'];

	$result = jsonRequest($url, json_encode($request));

	header('Content-Type: application/json');

	if (!empty($_REQUEST['raw']))
		die($result);
	$json = json_decode($result, TRUE);
	//print_r($json);

	print json_encode($json['candidates'][0]['content']['parts']);
	exit;
}

##############################

?>

<form name="theForm" onsubmit="return false" style="background-color:#eee;padding:10px;">
	Prompt: <textarea name=input rows=4 cols=50 wordwrap=soft>List the churches in Crawley</textarea><br>
	Query: <input type=search name=query value="churchs in crawley" size=60><input type=button value="Suggest" onclick="getquerysuggestion()">
	<input type=button value=Run onclick="runquery()">
	<input type=button value=Final onclick="finaloutput()"><br>
	<input type=checkbox name=titles checked>Titles, 
	<input type=checkbox name=comments>Descriptions, 
	<input type=checkbox name=snippets>Shared Descriptions, <br>


	<div id="result" style="float:left;width:50%;background-color:yellow">
	</div>

	<textarea style="float:left;width:50%;background-color:lightgreen" name=output rows=60 cols=60></textarea>
</form>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>

<script>

function getquerysuggestion() {
	let form = document.forms['theForm'];
	let input = form.elements['input'].value;

	let prompt = "Please suggest a keywords search engine query to look for images for this prompt:\n\n"+
			input + "\n\n"+
			"Please just output the query, nothing else. The query does not need to contain the word images, assume it being run against a image search engine. Also does not need to mention UK, United Kingdom, or British Isles";

	$.ajax({
	  type: "POST",
	  url: "?",
	  data: {input: prompt},
	  success: function(result) {
		form.elements['query'].value = result[0].text;
          }
	});
}

var rows = null;
function runquery() {
	let form = document.forms['theForm'];
	let query = form.elements['query'].value;
	//rupmentry conversion of a list of keywords to sphinx format
	if (query.match(/^(\w+[\w ]+)(, \w+[\w ]+)+$/)) {
		query = "("+query.replace(/, /g,') | (')+")";
	}
	let data = {
		select: "id,title",
		match: query,
		group: "title",
		limit: 100
	};
        $.ajax({
          url: "https://api.geograph.org.uk/api-facetql.php",
          data: data,
          success: function(result) {
		if (result.rows) {
			rows = result.rows;
			let $ele = $('#result').empty();
			if (form.elements['titles'].checked) {
				for(let q=0;q<result.rows.length;q++) {
					$ele.append($('<div/>').text(result.rows[q].id+': '+result.rows[q].title));
				}
			}
			if (form.elements['comments'].checked) //|| snipets?
				addcomments(rows);
		} else {
			alert("No Images found, try a different query");
		}
          }
        });
}

function addcomments(rows) {
    let form = document.forms['theForm'];

    // 1. Prepare data using map and join
    const ids = rows.map(row => row.id).join(',');
    const requestData = {
        ids: ids,
    };

    $.ajax({
        type: "POST",
        url: "descriptions.json.php",
        data: requestData,
        dataType: 'json', // Ensure jQuery parses the JSON response
        success: function(response) {
            console.log(response);

            // Check for a server error first
            if (response.error) {
                $('#result').empty().append($('<div/>').text(`Error loading images: ${response.error}`));
                return;
            }

            // Reference the image comments from the new, consistent structure
            const imageComments = response.images || {};
            const $ele = $('#result').empty();
            const showTitles = form.elements['titles'].checked;

            // 2. Iterate and render using forEach
            rows.forEach(row => {
                const imageId = row.id;

                // Option 1: Append a wrapper div for clean structure
                const $itemDiv = $('<div/>', { class: 'image-item' });

                // Add Title (if checked)
                if (showTitles) {
                    $itemDiv.append($('<div/>', { class: 'image-title' }).text(row.title));
                }

                // Add Comment (using the new response structure: response.images)
                if (imageComments[imageId]) {
                    $itemDiv.append($('<div/>', { class: 'image-comment' }).text(imageComments[imageId]));
                }

                $ele.append($itemDiv);
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
             // Handle network or JSON parsing errors
             $('#result').empty().append($('<div/>').text(`AJAX Error: ${textStatus} - ${errorThrown}`));
        }
    });
}


function finaloutput() {
	let form = document.forms['theForm'];
        let input = form.elements['input'].value;
        let results = [];
	for(let q=0;q<rows.length;q++) {
                 results.push(rows[q].title);
        }


        let prompt = "Please answer the following query, just using the provided Context:\n\n"+
                        input + "\n\n"+
			"Context, which is a list of image title/captions: \n"+
			results.join("\n");

        $.ajax({
          type: "POST",
          url: "?",
          data: {input: prompt},
          success: function(result) {
                form.elements['output'].value = result[0].text;
          }
        });

}

</script>



<?

##############################
















//////////////////////////////////

function jsonRequest($url,&$data) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$data);  //Post Fields
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//curl_setopt($ch, CURLOPT_VERBOSE, true);

	$headers = array();
	$headers[] = 'Content-Type: application/json';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$server_output = curl_exec ($ch);

	curl_close ($ch);

	return $server_output ;
}

