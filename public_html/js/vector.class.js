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
   * @returns {EmbeddingVector} A new vector that is the sum of the two vectors.
   */
  add(otherVector) {
    if (this.dimension !== otherVector.dimension) {
      throw new Error('Vectors must have the same dimension for addition.');
    }
    const newVector = new Float32Array(this.dimension);
    for (let i = 0; i < this.dimension; i++) {
      newVector[i] = this.vector[i] + otherVector.vector[i];
    }
    return new EmbeddingVector(newVector);
  }

  /**
   * Subtracts another vector from this vector.
   * @param {EmbeddingVector} otherVector - The vector to subtract.
   * @returns {EmbeddingVector} A new vector that is the difference of the two vectors.
   */
  subtract(otherVector) {
    if (this.dimension !== otherVector.dimension) {
      throw new Error('Vectors must have the same dimension for subtraction.');
    }
    const newVector = new Float32Array(this.dimension);
    for (let i = 0; i < this.dimension; i++) {
      newVector[i] = this.vector[i] - otherVector.vector[i];
    }
    return new EmbeddingVector(newVector);
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
}

// Example Usage:

// Helper function to create a random 512-dim vector for demonstration
const createRandomVector = () => {
  const arr = new Float32Array(512);
  for (let i = 0; i < 512; i++) {
    arr[i] = Math.random() * 2 - 1; // Values between -1 and 1
  }
  return new EmbeddingVector(arr);
};

// 1. Create a vector from a plain array of numbers
const plainArray = [1.0, 2.0, 3.0, 4.0, ...new Array(508).fill(0)];
const vectorFromArray = new EmbeddingVector(plainArray);
console.log('Vector from plain array (first 4 elements):', vectorFromArray.vector.slice(0, 4));

// 2. Demonstrate the new distance method
const vector1 = createRandomVector();
const vector2 = createRandomVector();
const dist = vector1.distance(vector2);
console.log('\nDistance between two random vectors:', dist);

// 3. Demonstrate KNN using the new distance method
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
