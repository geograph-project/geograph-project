<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>D3 Force-Directed Graph with Image Nodes</title>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="/js/vector.class.js"></script>
    <script src="/js/geograph-api-libs.js?<? echo filemtime("../js//geograph-api-libs.js"); ?>"></script>
    <style>
        body { margin: 0; overflow: hidden; background-color: #f0f0f0; }
        .link {
            stroke: #999;
            stroke-opacity: 0.6;
            stroke-width: 2px;
        }
        .node-group {
            cursor: grab;
        }
    </style>
</head>
<body>
    <svg id="force-graph"></svg>

    <script src="graph.js?<? echo filemtime('graph.js'); ?>">    </script>

<script>
const nodeSize = 120; // Diameter of the image node
// 1. Prepare Data
const data = {};


       let paramname = 'match';
       let query = '@place llan ffestiniog';
       let numClusters = 8;

<?

 if (!empty($_GET['query']))
	print "query = ".json_encode($_GET['query'])."\n";
if (!empty($_GET['clusters']))
	print "numClusters = ".min(100,max(2,intval($_GET['clusters'])))."\n";
?>

$.ajax({
            url: '/api-facetql-vector.php',
            method: 'GET',
            data: {
                [paramname]: query,
                select: 'id,hash,grid_reference,realname,title,image_vector,place',
                long: 1,
                utf: 1,
                limit: 100
            },
            dataType: 'json'

}).done(function(imageResults) {

    if (!imageResults || !imageResults.rows || imageResults.rows.length === 0) {
        $resultsContainer.html('<p>No images found for the given query.</p>');
        return;
    }

    const imageVectors = []; // Stores normalized EmbeddingVector objects
    const imageData = [];     // Stores the original image result objects
    const unprocessed = [];
    
    // --- 1. Prepare Nodes and Vectors ---
    // This loop populates imageVectors and imageData for clustering,
    // and also creates the initial 'nodes' array for the D3 graph.
    const nodes = []; 

    imageResults.rows.forEach(function(image) {
        const imageUrl = getGeographUrl(image.id, image.hash, 'small');
        
        // Add the image to the nodes array
        const newNode = {
            id: String(image.id), // Ensure IDs are unique strings for D3 linking
            url: imageUrl,
            data: image, // Store all original data
            vector: null, // Placeholder for the normalized vector
            cluster: null // Placeholder for cluster ID
        };
        nodes.push(newNode);
        
        if (image.image_vector) {
            try {
                const vector = new EmbeddingVector(image.image_vector).normalize();
                imageVectors.push(vector);
                // Also store a reference to the node in imageData to maintain index correspondence
                imageData.push(newNode); 
                newNode.vector = vector;
            } catch (e) {
                console.error(`Could not process vector for image ID ${image.id}:`, e);
                unprocessed.push(image);
                // Remove from nodes if vector is essential
                nodes.pop(); 
            }
        } else {
            unprocessed.push(image);
            nodes.pop(); // Remove non-vector nodes
        }
    });

    if (imageVectors.length < 2) {
        // Need at least 2 images for links/clustering
        console.warn("Not enough images with vectors to create a graph.");
        return;
    }

    // Perform K-means clustering
    const clusters = EmbeddingVector.kmeans(imageVectors, Math.min(numClusters, imageVectors.length));

    // Group and identify the "cluster representative" images
    const clusterRepresentatives = [];
    
    clusters.forEach((cluster, i) => {
        // Map cluster indices back to the 'nodes' array for easy access
        cluster.nodes = cluster.indices.map(index => imageData[index]); 
        
        // Assign cluster ID to each node in this cluster
        cluster.nodes.forEach(node => {
            node.cluster = i;
        });
        
        // Take the first image of the cluster as the representative
        if (cluster.nodes.length > 0) {
            clusterRepresentatives.push(cluster.nodes[0]);
        }
    });

    // --- 2. Create Links ---
    const links = [];

    // --- 2a. Inter-Cluster Links (Representative to Representative) ---
    for (let i = 0; i < clusterRepresentatives.length; i++) {
        for (let j = i + 1; j < clusterRepresentatives.length; j++) {
            const nodeA = clusterRepresentatives[i];
            const nodeB = clusterRepresentatives[j];
            
            // Calculate link length (distance) using the EmbeddingVector method
            // Cosine distance: 0 (most similar) to 2 (most dissimilar)
            const distanceValue = nodeA.vector.distance(nodeB.vector);

            links.push({
                source: nodeA.id,
                target: nodeB.id,
                value: distanceValue*2.7, // Store the distance for use in D3
                type: 'inter'
            });
        }
    }
    
    // --- 2b. Intra-Cluster Links (Representative to all others in its cluster) ---
    clusters.forEach(cluster => {
        const representative = cluster.nodes[0];
        
        // Link the representative to every other node in the cluster
        for (let i = 1; i < cluster.nodes.length; i++) { // Start at index 1 (skips the representative itself)
            const otherNode = cluster.nodes[i];
            
            // Calculate link length
            const distanceValue = representative.vector.distance(otherNode.vector);

            links.push({
                source: representative.id,
                target: otherNode.id,
                value: distanceValue, // Store the distance
                type: 'intra'
            });
        }
    });

console.log('n', nodes);

    // --- 3. Run the D3 Force Graph ---
    
    // Define the link distance function: Map cosine distance (0 to 2) to pixel length (e.g., 20px to 150px)
    // The link length should be proportional to the cosine distance (more distance = longer link)
    const minD3Length = nodeSize*1; //30;
    const maxD3Length = nodeSize*6; //150;
    const distanceRange = maxD3Length - minD3Length;
    const maxCosineDistance = 1.0;

    function calculateD3LinkDistance(linkData) {
        // Normalize cosine distance (value) from 0-2 to 0-1
        const normalizedValue = Math.min(1, linkData.value / maxCosineDistance);


console.log(linkData, normalizedValue, minD3Length + (normalizedValue * distanceRange));

        
        // Map 0-1 to the desired pixel range
        return minD3Length + (normalizedValue * distanceRange);
    }


console.log('l', links);

    // Now call your graph rendering function with the new data
    // Assuming you have a function called `load_force_directed_graph`
    load_force_directed_graph({ 
        nodes: nodes, 
        links: links, 
        svgSelector: '#force-graph', // Use your SVG ID
        calculateD3LinkDistance: calculateD3LinkDistance
    }); 
    
    // Note: You must ensure your `load_force_directed_graph` function 
    // uses `calculateD3LinkDistance` for the link force.

});



</script>
</body>
</html>
