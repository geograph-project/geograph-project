/**
 * A basic class for manipulating 512-dimensional embedding vectors.
 * The class can be constructed from an array of floats, a plain array of numbers or strings,
 * or a base64 encoded string.
 * It provides methods for vector arithmetic, normalization, cosine similarity,
 * and a simple K-Nearest Neighbors (KNN) implementation.
 */
class EmbeddingVector {
  /**
   * Constructs an EmbeddingVector.
   * @param {Float32Array | Array<number | string> | string} data - An array of 512 floats,
   * a regular array of numbers or strings, or a base64 encoded string.
   */
  constructor(data) {
    this.dimension = 512;
    this.vector = this._processInput(data);
  }

  /**
   * Processes the constructor input to create a Float32Array.
   * @param {any} data - The input data.
   * @returns {Float32Array} The resulting 512-dimensional vector.
   * @private
   */
  _processInput(data) {
    if (typeof data === 'string') {
      return this._decodeBase64(data);
    } else if (data instanceof Float32Array) {
      if (data.length !== this.dimension) {
        throw new Error(`Invalid vector dimension. Expected ${this.dimension}, got ${data.length}`);
      }
      return data;
    } else if (Array.isArray(data)) {
      if (data.length !== this.dimension) {
        throw new Error(`Invalid vector dimension. Expected ${this.dimension}, got ${data.length}`);
      }
      const floatArray = new Float32Array(this.dimension);
      for (let i = 0; i < this.dimension; i++) {
        const parsedValue = parseFloat(data[i]);
        if (isNaN(parsedValue)) {
          throw new Error(`Invalid value in array at index ${i}: could not be parsed to a float.`);
        }
        floatArray[i] = parsedValue;
      }
      return floatArray;
    } else {
      throw new Error('Invalid constructor input. Must be a Float32Array, a plain array, or a base64 string.');
    }
  }

  /**
   * Decodes a base64 string into a Float32Array.
   * Assumes the string was created using PHP's `pack('g*')`, which packs floats
   * in the machine's native format.
   * @param {string} base64String - The base64 encoded string.
   * @returns {Float32Array} The decoded vector.
   * @private
   */
  _decodeBase64(base64String) {
    const binaryString = atob(base64String);
    const len = binaryString.length;
    const buffer = new ArrayBuffer(len);
    const view = new DataView(buffer);
    for (let i = 0; i < len; i++) {
      view.setUint8(i, binaryString.charCodeAt(i));
    }
    return new Float32Array(buffer);
  }

  /**
   * Adds another vector to this vector.
   * @param {EmbeddingVector} otherVector - The vector to add.
   * @param {boolean} [normalize=true] - Whether to normalize the resulting vector.
   * @returns {EmbeddingVector} A new vector that is the sum of the two vectors.
   */
  add(otherVector, normalize = true) {
    if (this.dimension !== otherVector.dimension) {
      throw new Error('Vectors must have the same dimension for addition.');
    }
    const newVector = new Float32Array(this.dimension);
    for (let i = 0; i < this.dimension; i++) {
      newVector[i] = this.vector[i] + otherVector.vector[i];
    }
    const resultVector = new EmbeddingVector(newVector);
    return normalize ? resultVector.normalize() : resultVector;
  }

  /**
   * Subtracts another vector from this vector.
   * @param {EmbeddingVector} otherVector - The vector to subtract.
   * @param {boolean} [normalize=true] - Whether to normalize the resulting vector.
   * @returns {EmbeddingVector} A new vector that is the difference of the two vectors.
   */
  subtract(otherVector, normalize = true) {
    if (this.dimension !== otherVector.dimension) {
      throw new Error('Vectors must have the same dimension for subtraction.');
    }
    const newVector = new Float32Array(this.dimension);
    for (let i = 0; i < this.dimension; i++) {
      newVector[i] = this.vector[i] - otherVector.vector[i];
    }
    const resultVector = new EmbeddingVector(newVector);
    return normalize ? resultVector.normalize() : resultVector;
  }

  /**
   * Computes the average of a list of vectors.
   * @param {Array<EmbeddingVector>} vectors - The list of vectors to average.
   * @param {boolean} [normalize=true] - Whether to normalize the resulting vector.
   * @returns {EmbeddingVector} A new vector representing the average.
   */
  static average(vectors, normalize = true) {
    if (vectors.length === 0) {
      throw new Error('Input array cannot be empty.');
    }

    const dimension = vectors[0].dimension;
    const sumVector = new Float32Array(dimension).fill(0);

    for (const vector of vectors) {
      if (vector.dimension !== dimension) {
        throw new Error('All vectors must have the same dimension.');
      }
      for (let i = 0; i < dimension; i++) {
        sumVector[i] += vector.vector[i];
      }
    }

    const averageVector = new Float32Array(dimension);
    for (let i = 0; i < dimension; i++) {
      averageVector[i] = sumVector[i] / vectors.length;
    }

    const resultVector = new EmbeddingVector(averageVector);
    return normalize ? resultVector.normalize() : resultVector;
  }

  /**
   * Computes a robust average of a list of vectors by mitigating outliers.
   * It first computes a general average, then uses KNN to select the 80th percentile
   * of closest vectors, and finally computes a new average from that subset.
   * @param {Array<EmbeddingVector>} vectors - The list of vectors to average.
   * @param {number} [percentage=0.80] - The percentile of closest vectors to include in the final average.
   * @returns {EmbeddingVector} A new vector representing the robust average.
   */
  static robustAverage(vectors, percentage = 0.80) {
    // Handle edge cases
    if (vectors.length === 0) {
      throw new Error('Input array cannot be empty.');
    }
    if (vectors.length < 5) {
      console.warn('Robust average requires a larger dataset for meaningful results. Returning a regular average.');
      return this.average(vectors);
    }
    if (percentage <= 0 || percentage > 1) {
      throw new Error('Percentage must be a value between 0 and 1.');
    }

    // 1. Compute initial average to act as a centroid
    // Don't normalize the initial average so it reflects the raw data position
    const initialAverage = this.average(vectors, false);

    // 2. Create candidate map for KNN search
    const candidates = {};
    for (let i = 0; i < vectors.length; i++) {
      candidates[i] = vectors[i];
    }

    // 3. Use KNN to find the closest 'k' vectors
    const k = Math.floor(vectors.length * percentage);
    const nearestNeighbors = initialAverage.knn(candidates, k);

    // 4. Create a new list of vectors from the filtered KNN results
    const filteredVectors = nearestNeighbors.map(neighbor => vectors[neighbor.key]);

    // 5. Compute the final average from the filtered list and normalize it
    return this.average(filteredVectors, true);
  }

  /**
   * Normalizes the vector to a unit vector (length 1).
   * @returns {EmbeddingVector} A new normalized vector.
   */
  normalize() {
    let sumOfSquares = 0;
    for (let i = 0; i < this.dimension; i++) {
      sumOfSquares += this.vector[i] * this.vector[i];
    }
    const magnitude = Math.sqrt(sumOfSquares);
    if (magnitude === 0) {
      return new EmbeddingVector(new Float32Array(this.dimension).fill(0));
    }
    const newVector = new Float32Array(this.dimension);
    for (let i = 0; i < this.dimension; i++) {
      newVector[i] = this.vector[i] / magnitude;
    }
    return new EmbeddingVector(newVector);
  }

  /**
   * Calculates the dot product of this vector and another.
   * @param {EmbeddingVector} otherVector - The vector to calculate the dot product with.
   * @returns {number} The dot product of the two vectors.
   * @private
   */
  _dotProduct(otherVector) {
    if (this.dimension !== otherVector.dimension) {
      throw new Error('Vectors must have the same dimension for dot product.');
    }
    let dotProduct = 0;
    for (let i = 0; i < this.dimension; i++) {
      dotProduct += this.vector[i] * otherVector.vector[i];
    }
    // Clamp the value to handle floating point inaccuracies
    return Math.max(-1.0, Math.min(1.0, dotProduct));
  }

  /**
   * Calculates the cosine similarity between this vector and another.
   * This method first normalizes both vectors to align with the PHP implementation.
   * @param {EmbeddingVector} otherVector - The vector to compare with.
   * @returns {number} The cosine similarity between the two vectors.
   */
  cosineSimilarity(otherVector) {
    const normalizedThis = this.normalize();
    const normalizedOther = otherVector.normalize();

    // The dot product of two normalized vectors is their cosine similarity
    const similarity = normalizedThis._dotProduct(normalizedOther);

    // Clamp the value to handle floating point inaccuracies
    return Math.max(-1.0, Math.min(1.0, similarity));
  }

  /**
   * Calculates the cosine distance between this vector and another.
   * This assumes both vectors are already normalized.
   * @param {EmbeddingVector} otherVector - The vector to compare with.
   * @returns {number} The cosine distance between the two vectors.
   */
  distance(otherVector) {
      return 1.0 - this._dotProduct(otherVector);
  }

  /**
   * Finds the K-Nearest Neighbors for this vector from a list of candidates.
   * @param {Object<string, EmbeddingVector>} candidates - An associative array where keys are identifiers
   * and values are EmbeddingVector instances.
   * @param {number} k - The number of nearest neighbors to return.
   * @returns {Array<Object>} An array of objects, where each object has a `key` and `distance` property,
   * sorted by distance in ascending order.
   */
  knn(candidates, k) {
    const distances = [];

    for (const key in candidates) {
      if (Object.prototype.hasOwnProperty.call(candidates, key)) {
        const candidateVector = candidates[key];
        const distance = this.distance(candidateVector);
        distances.push({ key, distance });
      }
    }

    distances.sort((a, b) => a.distance - b.distance);

    return distances.slice(0, k);
  }

  /**
   * Performs a Farthest First Traversal on a list of vectors.
   * This algorithm greedily selects the vector that is farthest from all
   * previously selected vectors, starting with the first vector in the input list.
   * @param {Array<EmbeddingVector>} candidates - A list of vectors to traverse.
   * @returns {Array<number>} The sorted list of indices from the original `candidates` array.
   */
  static farthestFirstTraversal(candidates) {
    if (candidates.length === 0) {
      return [];
    }

    // Use a copy to avoid modifying the original array
    const remaining = candidates.map((vector, index) => ({ vector, index }));
    const resultIndices = [];

    // The first vector in the list is the starting point.
    const startVector = remaining.shift();
    resultIndices.push(startVector.index);

    while (remaining.length > 0) {
      let maxMinDistance = -Infinity;
      let farthestIndexInRemaining = -1;
      let farthestVectorInRemaining = null;

      // Find the vector in `remaining` that has the maximum minimum distance to any vector already selected.
      for (let i = 0; i < remaining.length; i++) {
        const candidate = remaining[i];
        let minDistance = Infinity;

        // Calculate the minimum distance from the current candidate to the result set.
        for (const resultIndex of resultIndices) {
          const selected = candidates[resultIndex];
          const distance = candidate.vector.distance(selected);
          if (distance < minDistance) {
            minDistance = distance;
          }
        }

        // If this minimum distance is the greatest so far, mark it as the farthest.
        if (minDistance > maxMinDistance) {
          maxMinDistance = minDistance;
          farthestVectorInRemaining = candidate;
          farthestIndexInRemaining = i;
        }
      }

      // Add the farthest vector's index to the result and remove it from the remaining candidates.
      if (farthestVectorInRemaining) {
        resultIndices.push(farthestVectorInRemaining.index);
        remaining.splice(farthestIndexInRemaining, 1);
      }
    }

    return resultIndices;
  }

  /**
   * Performs K-means clustering on a list of vectors.
   * It uses the Farthest First Traversal algorithm to initialize the centroids.
   * @param {Array<EmbeddingVector>} candidates - The list of vectors to cluster.
   * @param {number} k - The number of clusters to form.
   * @param {number} [maxIterations=100] - The maximum number of iterations.
   * @returns {Array<Object>} An array of clusters, each containing a `centroid` vector
   * and the `indices` of the vectors belonging to that cluster.
   */
  static kmeans(candidates, k, maxIterations = 100) {
    if (k > candidates.length || k <= 0) {
      throw new Error('Invalid value for k. k must be greater than 0 and less than or equal to the number of candidates.');
    }

    // Use Farthest First Traversal to get initial centroids
    const initialCentroidIndices = this.farthestFirstTraversal(candidates).slice(0, k);
    let centroids = initialCentroidIndices.map(index => candidates[index]);

    let clusters = Array.from({ length: k }, () => []);
    let iterations = 0;
    let hasChanged = true;

    while (hasChanged && iterations < maxIterations) {
      const newClusters = Array.from({ length: k }, () => []);
      for (let i = 0; i < candidates.length; i++) {
        const vector = candidates[i];
        let minDistance = Infinity;
        let closestCentroidIndex = -1;

        for (let j = 0; j < k; j++) {
          const distance = vector.distance(centroids[j]);
          if (distance < minDistance) {
            minDistance = distance;
            closestCentroidIndex = j;
          }
        }
        newClusters[closestCentroidIndex].push(i);
      }

      hasChanged = false;
      for (let i = 0; i < k; i++) {
        if (newClusters[i].length !== clusters[i].length || !newClusters[i].every(index => clusters[i].includes(index))) {
            hasChanged = true;
            break;
        }
      }
      if (!hasChanged) {
        clusters = newClusters;
        break;
      }
      clusters = newClusters;

      const newCentroids = [];
      for (let i = 0; i < k; i++) {
        if (clusters[i].length > 0) {
          const sumVector = new Float32Array(candidates[0].dimension).fill(0);
          for (const index of clusters[i]) {
            const vector = candidates[index].vector;
            for (let j = 0; j < vector.length; j++) {
              sumVector[j] += vector[j];
            }
          }

          const newCentroidVector = new Float32Array(candidates[0].dimension);
          for (let j = 0; j < sumVector.length; j++) {
            newCentroidVector[j] = sumVector[j] / clusters[i].length;
          }

          const newCentroid = new EmbeddingVector(newCentroidVector);
          newCentroids.push(newCentroid.normalize());
        } else {
          newCentroids.push(new EmbeddingVector(new Float32Array(candidates[0].dimension).fill(0)).normalize());
        }
      }
      centroids = newCentroids;
      iterations++;
    }

    return clusters.map((indices, i) => ({
      centroid: centroids[i],
      indices: indices
    }));
  }

}

// -----------------------------------------------------------------------------
// EXAMPLE USAGE: This function contains demonstration code for the class.
// It will not run automatically. To run the examples, call runExamples()
// from your browser's developer console or another script.
// -----------------------------------------------------------------------------
function runExamples() {
  /**
   * Helper function to create a random 512-dim vector for demonstration.
   * @returns {EmbeddingVector} A new EmbeddingVector instance.
   */
  const createRandomVector = () => {
    const arr = new Float32Array(512);
    for (let i = 0; i < 512; i++) {
      arr[i] = Math.random() * 2 - 1; // Values between -1 and 1
    }
    return new EmbeddingVector(arr);
  };

  /**
   * Helper function to create a vector near a specific "seed" vector.
   * @param {EmbeddingVector} seedVector - The vector to base the new vector on.
   * @param {number} noise - The amount of random noise to add.
   * @returns {EmbeddingVector} A new EmbeddingVector instance.
   */
  const createVectorNearSeed = (seedVector, noise) => {
    const arr = new Float32Array(seedVector.dimension);
    for (let i = 0; i < seedVector.dimension; i++) {
      arr[i] = seedVector.vector[i] + (Math.random() * noise * 2 - noise);
    }
    return new EmbeddingVector(arr).normalize();
  };

  // 1. Create a vector from a plain array of numbers
  const plainArray = [1.0, 2.0, 3.0, 4.0, ...new Array(508).fill(0)];
  const vectorFromArray = new EmbeddingVector(plainArray);
  console.log('Vector from plain array (first 4 elements):', vectorFromArray.vector.slice(0, 4));

  // 2. Demonstrate the distance method
  const vector1 = createRandomVector();
  const vector2 = createRandomVector();
  const dist = vector1.distance(vector2);
  console.log('\nDistance between two random vectors:', dist);

  // 3. Demonstrate KNN
  const queryVector = createRandomVector();
  const candidateVectors = {};

  // Create 100 random candidate vectors
  for (let i = 0; i < 100; i++) {
    candidateVectors[`id_${i}`] = createRandomVector();
  }

  // Add a vector that is very similar to the query vector
  const verySimilarVector = new EmbeddingVector(queryVector.vector.map(val => val + (Math.random() * 0.01 - 0.005)));
  candidateVectors['very_similar_id'] = verySimilarVector;

  const k = 5;
  const nearestNeighbors = queryVector.knn(candidateVectors, k);
  console.log(`\n${k} Nearest Neighbors for the query vector:`);
  console.log(nearestNeighbors);

  // 4. Demonstrate compatibility with base64 decoding (using a simple mock)
  const originalVector = new EmbeddingVector(new Float32Array([1.0, 2.0, 3.0, 4.0, ...new Array(508).fill(0)]));
  const floatArray = originalVector.vector;
  const byteBuffer = new ArrayBuffer(floatArray.length * 4);
  const dataView = new DataView(byteBuffer);
  for (let i = 0; i < floatArray.length; i++) {
      dataView.setFloat32(i * 4, floatArray[i], true); // true for little-endian
  }
  const binaryString = Array.from(new Uint8Array(byteBuffer)).map(byte => String.fromCharCode(byte)).join('');
  const base64String = btoa(binaryString);

  console.log('\nBase64 encoded string:', base64String);
  const decodedVector = new EmbeddingVector(base64String);
  console.log('Vector decoded from base64:', decodedVector.vector.slice(0, 4));
  console.log('Original and decoded vectors are equal:', originalVector.vector[0] === decodedVector.vector[0] && originalVector.vector[1] === decodedVector.vector[1]);

  // 5. Demonstrate the farthestFirstTraversal method
  const traversalCandidates = [
    new EmbeddingVector([1, 0, ...new Array(510).fill(0)]),
    new EmbeddingVector([-1, 0, ...new Array(510).fill(0)]),
    new EmbeddingVector([0, 1, ...new Array(510).fill(0)]),
    new EmbeddingVector([0.5, 0, ...new Array(510).fill(0)]),
    new EmbeddingVector([-0.5, 0, ...new Array(510).fill(0)]),
  ];
  console.log('\nFarthest First Traversal Example:');
  const traversalResult = EmbeddingVector.farthestFirstTraversal(traversalCandidates);
  console.log('Traversal order (indices):', traversalResult);

  // 6. Demonstrate the kmeans method
  console.log('\nK-means Clustering Example:');
  const numClusters = 3;
  const numVectors = 60;
  const kmeansCandidates = [];

  const seed1 = createRandomVector();
  const seed2 = createRandomVector();
  const seed3 = createRandomVector();

  for (let i = 0; i < numVectors / numClusters; i++) {
    kmeansCandidates.push(createVectorNearSeed(seed1, 0.1));
    kmeansCandidates.push(createVectorNearSeed(seed2, 0.1));
    kmeansCandidates.push(createVectorNearSeed(seed3, 0.1));
  }
  
  const clusteringResult = EmbeddingVector.kmeans(kmeansCandidates, numClusters);
  console.log(`Clustering result for ${numVectors} vectors into ${numClusters} clusters:`);
  clusteringResult.forEach((cluster, index) => {
    console.log(`- Cluster ${index + 1}: contains ${cluster.indices.length} vectors. Centroid (first 4 elements): [${cluster.centroid.vector[0].toFixed(2)}, ${cluster.centroid.vector[1].toFixed(2)}, ${cluster.centroid.vector[2].toFixed(2)}, ${cluster.centroid.vector[3].toFixed(2)}]`);
  });

  // 7. Demonstrate the average method
  console.log('\nAverage Method Example:');
  const vectorsToAverage = [
    new EmbeddingVector([10, 20, ...new Array(510).fill(0)]),
    new EmbeddingVector([20, 30, ...new Array(510).fill(0)]),
    new EmbeddingVector([30, 40, ...new Array(510).fill(0)]),
  ];
  const averagedVectorNormalized = EmbeddingVector.average(vectorsToAverage, true);
  const averagedVectorNotNormalized = EmbeddingVector.average(vectorsToAverage, false);
  console.log('Original vectors (first 2 elements):');
  vectorsToAverage.forEach(v => console.log(`[${v.vector[0]}, ${v.vector[1]}]`));
  console.log('Averaged vector (normalized):');
  console.log(`[${averagedVectorNormalized.vector[0].toFixed(2)}, ${averagedVectorNormalized.vector[1].toFixed(2)}]`);
  console.log('Averaged vector (not normalized):');
  console.log(`[${averagedVectorNotNormalized.vector[0].toFixed(2)}, ${averagedVectorNotNormalized.vector[1].toFixed(2)}]`);

  // 8. Demonstrate the new add method with and without normalization
  console.log('\nAdd Method with Normalization Example:');
  const vectorAdd1 = new EmbeddingVector([10, 0, ...new Array(510).fill(0)]);
  const vectorAdd2 = new EmbeddingVector([0, 10, ...new Array(510).fill(0)]);
  const addedVectorNormalized = vectorAdd1.add(vectorAdd2, true);
  const addedVectorNotNormalized = vectorAdd1.add(vectorAdd2, false);
  console.log('Original vectors (first 2 elements):');
  console.log(`Vector 1: [${vectorAdd1.vector[0]}, ${vectorAdd1.vector[1]}]`);
  console.log(`Vector 2: [${vectorAdd2.vector[0]}, ${vectorAdd2.vector[1]}]`);
  console.log('Added vector (normalized):');
  console.log(`[${addedVectorNormalized.vector[0].toFixed(2)}, ${addedVectorNormalized.vector[1].toFixed(2)}]`);
  console.log('Added vector (not normalized):');
  console.log(`[${addedVectorNotNormalized.vector[0].toFixed(2)}, ${addedVectorNotNormalized.vector[1].toFixed(2)}]`);

  // 9. Demonstrate the new subtract method with and without normalization
  console.log('\nSubtract Method with Normalization Example:');
  const vectorSub1 = new EmbeddingVector([20, 20, ...new Array(510).fill(0)]);
  const vectorSub2 = new EmbeddingVector([10, 10, ...new Array(510).fill(0)]);
  const subtractedVectorNormalized = vectorSub1.subtract(vectorSub2, true);
  const subtractedVectorNotNormalized = vectorSub1.subtract(vectorSub2, false);
  console.log('Original vectors (first 2 elements):');
  console.log(`Vector 1: [${vectorSub1.vector[0]}, ${vectorSub1.vector[1]}]`);
  console.log(`Vector 2: [${vectorSub2.vector[0]}, ${vectorSub2.vector[1]}]`);
  console.log('Subtracted vector (normalized):');
  console.log(`[${subtractedVectorNormalized.vector[0].toFixed(2)}, ${subtractedVectorNormalized.vector[1].toFixed(2)}]`);
  console.log('Subtracted vector (not normalized):');
  console.log(`[${subtractedVectorNotNormalized.vector[0].toFixed(2)}, ${subtractedVectorNotNormalized.vector[1].toFixed(2)}]`);
}

