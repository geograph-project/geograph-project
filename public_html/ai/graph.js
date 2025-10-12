const width = window.innerWidth;
const height = window.innerHeight;

// Define a function to calculate distance from the link's 'value'
function calculateLinkDistance(linkData) {
    // We'll map the link's 'value' (10 to 80) to a distance (50 to 150)
    // You can use any custom formula here.
    const baseDistance = nodeSize * 2;
    const distanceScale = 1.2;
    
    // Custom calculation:
    return baseDistance + linkData.value * distanceScale; 
    // Example: A link with value 10 will have a distance of 50 + 12 = 62px
    // Example: A link with value 80 will have a distance of 50 + 96 = 146px
}


function load_force_directed_graph({ nodes, links, svgSelector, calculateD3LinkDistance }) {

	// 2. Create SVG Container
	const svg = d3.select(svgSelector)
	    .attr("width", width)
	    .attr("height", height);

	// Create a group to hold everything (links and nodes)
	const g = svg.append("g");

	// 3. Define the Force Simulation
	const simulation = d3.forceSimulation(nodes)
	    // Link force: Attracts connected nodes
	    .force("link", d3.forceLink(links)
	        .id(d => d.id)
	        // Pass the function instead of a fixed number
	        .distance(calculateD3LinkDistance)
	    )
	    // Charge (Many-Body) force: Repels all nodes from each other
	    .force("charge", d3.forceManyBody().strength(-300))
	    // Center force: Pulls nodes towards the center of the SVG
	    .force("center", d3.forceCenter(width / 2, height / 2))
	    // Collision force: Prevents nodes from overlapping
	    .force("collide", d3.forceCollide().radius(nodeSize / 2 + 5));

	// 4. Draw Links
	const link = g.append("g")
	    .attr("class", "links")
	    .selectAll("line")
	    .data(links)
	    .join("line")
	    .attr("class", "link");

	// 5. Draw Nodes (using <g> and <image> for custom node shape)
	// We use a <g> (group) element for each node to attach the drag behavior and position the image
	const node = g.append("g")
	    .attr("class", "nodes")
	    .selectAll(".node-group")
	    .data(nodes)
	    .join("g")
	    .attr("class", "node-group")
	    .call(drag(simulation)); // Apply drag interactivity

/*
	// Add a circle as a background/border for the image (optional, but good for visibility)
	node.append("circle")
	    .attr("r", nodeSize / 2) // Radius is half the nodeSize
	    .attr("fill", "white")
	    .attr("stroke", "#555")
	    .attr("stroke-width", 2);
*/
	// Add the image to the node group
	node.append("image")
	    .attr("xlink:href", d => d.url) // Set the image source
	    .attr("width", nodeSize)
	    .attr("height", nodeSize)
	    // Offset the image so its center aligns with the node's (x, y) coordinates
	    .attr("x", -(nodeSize / 2))
	    .attr("y", -(nodeSize / 2));

/*
	// Add a text label below the node (optional)
	node.append("text")
	    .attr("dy", nodeSize / 2 + 15) // Position below the image
	    .attr("text-anchor", "middle")
	    .text(d => d.id)
	    .attr("fill", "#333");
*/
	// 6. Update Positions on 'tick'
	simulation.on("tick", () => {
	    // Update link positions
	    link
	        .attr("x1", d => d.source.x)
	        .attr("y1", d => d.source.y)
	        .attr("x2", d => d.target.x)
	        .attr("y2", d => d.target.y);

	    // Update node group positions
	    node.attr("transform", d => `translate(${d.x},${d.y})`);
	});

}

// 7. Drag Functionality
function drag(simulation) {
    function dragstarted(event, d) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x; // fix the node's position on drag start
        d.fy = d.y;
    }

    function dragged(event, d) {
        d.fx = event.x; // update fixed position during drag
        d.fy = event.y;
    }

    function dragended(event, d) {
        if (!event.active) simulation.alphaTarget(0);
        // Optional: uncomment the next two lines to unfix the node's position after release
        d.fx = null; 
        d.fy = null; 
    }

    return d3.drag()
        .on("start", dragstarted)
        .on("drag", dragged)
        .on("end", dragended);
}
