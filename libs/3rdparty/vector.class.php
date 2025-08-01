<?php

/**
 * Copyright (c) 2025 AI.
 * This code is provided "as is" without warranty of any kind, express or implied.
 *
 * Class EmbeddingVector
 *
 * A small, compact PHP class for working with normalized embedding vectors,
 * typically derived from models like CLIP. It provides methods for common
 * vector operations such as distance calculation (cosine difference),
 * subtraction, averaging, and K-Nearest Neighbors (KNN) search.
 *
 * Assumes input vectors are already L2-normalized.
 *
 * Target PHP Version: 7.3+
 */
class EmbeddingVector
{
    /**
     * @var array The internal representation of the vector as an array of floats.
     */
    private array $vector;

    /**
     * Constructor for EmbeddingVector.
     *
     * Initializes the vector from either a packed binary string of floats
     * (e.g., from `pack('g*', ...)`) or a simple array of floats.
     *
     * @param string|array $data Either a packed string of floats (using 'g*' format for little-endian single-precision)
     * or an array of float/numeric values.
     * @param bool $normalize Optional. If true, the input vector will be L2-normalized upon construction.
     * @throws InvalidArgumentException If the input data is invalid (e.g., wrong type, unpack failure, non-numeric array elements).
     */
    public function __construct($data, bool $normalize = false)
    {
        if (is_string($data)) {
            // Unpack the string into an array of floats.
            // 'g*' unpacks single-precision floats (IEEE 754) in little-endian byte order.
            $unpacked = unpack('g*', $data);
            if ($unpacked === false || empty($unpacked)) {
                throw new InvalidArgumentException("Failed to unpack string data. Ensure it's a valid packed float string.");
            }
            // Re-index the array to be 0-based for consistent internal representation.
            $this->vector = array_values($unpacked);
        } elseif (is_array($data)) {
            // Validate that all array elements are numeric.
            foreach ($data as $value) {
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException("Array elements must be numeric.");
                }
            }
            // Ensure the array is 0-indexed.
            $this->vector = array_values($data);
        } else {
            throw new InvalidArgumentException("Input data must be a string (packed floats) or an array of floats.");
        }

        if (empty($this->vector)) {
            throw new InvalidArgumentException("Vector cannot be empty after initialization.");
        }

        // Normalize the vector if the $normalize flag is true
        if ($normalize) {
            $this->vector = self::normalizeVector($this->vector);
        }
    }

    /**
     * Returns the raw internal vector array.
     *
     * @return array The array of floats representing the vector.
     */
    public function getVector(): array
    {
        return $this->vector;
    }

    /**
     * Returns a compact binary string representation of the vector.
     *
     * This is useful for storing the vector efficiently in a database
     * or other storage system, and can be read back by the constructor.
     * The `pack` format 'g*' is used for single-precision floats (32-bit)
     * in little-endian byte order.
     *
     * @return string The packed binary string.
     */
    public function getBytes(): string
    {
        return pack('g*', ...$this->vector);
    }

    /**
     * Returns a new array representing the L2-normalized version of the internal vector.
     *
     * This is useful for chaining operations where an intermediate vector (e.g., from subtract or average)
     * needs to be normalized before being used for distance calculations.
     *
     * @return array The L2-normalized vector array.
     */
    public function getNormalizedVector(): array
    {
        return self::normalizeVector($this->vector);
    }

    /**
     * Calculates the cosine difference (distance) between this vector and another.
     *
     * Since both vectors are assumed to be L2-normalized, the cosine similarity
     * is simply their dot product. The cosine difference is then `1 - cosine_similarity`.
     * A result of 0 indicates identical vectors, and 2 indicates perfectly opposite vectors.
     *
     * @param EmbeddingVector|string|array $otherVector The other vector to compare against. Can be an EmbeddingVector object,
     * a packed string of floats, or an array of floats.
     * @return float The cosine difference.
     * @throws InvalidArgumentException If the dimensions of the two vectors do not match, or if input type is invalid.
     */
    public function distance($otherVector): float
    {
        // Cosine distance is 1 - cosine similarity (dot product of normalized vectors)
        return 1.0 - $this->dotProduct($otherVector);
    }

    /**
     * Calculates the dot product (scalar product) between this vector and another.
     *
     * If both vectors are L2-normalized, this result is equivalent to their cosine similarity.
     *
     * @param EmbeddingVector|string|array $otherVector The other vector to compare against. Can be an EmbeddingVector object,
     * a packed string of floats, or an array of floats.
     * @return float The dot product.
     * @throws InvalidArgumentException If the dimensions of the two vectors do not match, or if input type is invalid.
     */
    public function dotProduct($otherVector): float
    {
        $vec1 = $this->vector;

        // Ensure $otherVector is an EmbeddingVector instance for consistent access
        if (!($otherVector instanceof EmbeddingVector)) {
            // No automatic normalization here, as dot product can be calculated on non-normalized vectors.
            // However, for cosine similarity, inputs should be normalized.
            $otherVector = new EmbeddingVector($otherVector, false);
        }
        $vec2 = $otherVector->getVector();

        $dim = count($vec1);
        if ($dim !== count($vec2)) {
            throw new InvalidArgumentException("Vector dimensions must match for dot product calculation. Expected $dim, got " . count($vec2));
        }

        $dotProduct = 0.0;
        for ($i = 0; $i < $dim; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
        }

        // Clamp the dot product to the valid range [-1, 1] to mitigate floating point inaccuracies
        // when used as cosine similarity, though technically for raw dot product it can be outside.
        // For normalized vectors, it should be within [-1, 1].
        return max(-1.0, min(1.0, $dotProduct));
    }

    /**
     * Adds another vector to this vector.
     *
     * The resulting vector is a new `EmbeddingVector` instance.
     *
     * @param EmbeddingVector|string|array $otherVector The vector to add to this vector. Can be an EmbeddingVector object,
     * a packed string of floats, or an array of floats.
     * @param bool $normalize Optional. If true, the resulting vector will be L2-normalized upon creation.
     * @return EmbeddingVector A new `EmbeddingVector` instance representing the result of the addition.
     * @throws InvalidArgumentException If the dimensions of the two vectors do not match, or if input type is invalid.
     */
    public function add($otherVector, bool $normalize = false): EmbeddingVector
    {
        $vec1 = $this->vector;

        // Ensure $otherVector is an EmbeddingVector instance for consistent access
        if (!($otherVector instanceof EmbeddingVector)) {
            // No automatic normalization here for the *input* vector
            $otherVector = new EmbeddingVector($otherVector, false);
        }
        $vec2 = $otherVector->getVector();

        $dim = count($vec1);
        if ($dim !== count($vec2)) {
            throw new InvalidArgumentException("Vector dimensions must match for addition. Expected $dim, got " . count($vec2));
        }

        $resultVector = [];
        for ($i = 0; $i < $dim; $i++) {
            $resultVector[$i] = $vec1[$i] + $vec2[$i];
        }

        // Pass the $normalize flag to the constructor of the new EmbeddingVector
        return new EmbeddingVector($resultVector, $normalize);
    }

    /**
     * Subtracts another vector from this vector.
     *
     * The resulting vector is a new `EmbeddingVector` instance.
     *
     * @param EmbeddingVector|string|array $otherVector The vector to subtract from this vector. Can be an EmbeddingVector object,
     * a packed string of floats, or an array of floats.
     * @param bool $normalize Optional. If true, the resulting vector will be L2-normalized upon creation.
     * @return EmbeddingVector A new `EmbeddingVector` instance representing the result of the subtraction.
     * @throws InvalidArgumentException If the dimensions of the two vectors do not match, or if input type is invalid.
     */
    public function subtract($otherVector, bool $normalize = false): EmbeddingVector
    {
        $vec1 = $this->vector;

        // Ensure $otherVector is an EmbeddingVector instance for consistent access
        if (!($otherVector instanceof EmbeddingVector)) {
            // No automatic normalization here for the *input* vector, as subtraction result might not need to be normalized
            $otherVector = new EmbeddingVector($otherVector, false);
        }
        $vec2 = $otherVector->getVector();

        $dim = count($vec1);
        if ($dim !== count($vec2)) {
            throw new InvalidArgumentException("Vector dimensions must match for subtraction. Expected $dim, got " . count($vec2));
        }

        $resultVector = [];
        for ($i = 0; $i < $dim; $i++) {
            $resultVector[$i] = $vec1[$i] - $vec2[$i];
        }

        // Pass the $normalize flag to the constructor of the new EmbeddingVector
        return new EmbeddingVector($resultVector, $normalize);
    }

    /**
     * Computes the average vector from a list of `EmbeddingVector` instances.
     *
     * The resulting vector is a new `EmbeddingVector` instance.
     * IMPORTANT: The resulting vector is NOT automatically normalized.
     * If you intend to use it for further distance calculations, you may need
     * to normalize it using `EmbeddingVector::normalizeVector()` or can request normalized directly
     *
     * @param array<EmbeddingVector> $vectors An array containing `EmbeddingVector` objects.
     * @param bool $normalize Optional. If true, the resulting vector will be L2-normalized upon creation.
     * @return EmbeddingVector A new `EmbeddingVector` instance representing the average of the input vectors.
     * @throws InvalidArgumentException If the input list is empty or contains non-`EmbeddingVector` elements,
     * or if vectors have inconsistent dimensions.
     */
    public static function average(array $vectors, bool $normalize = false): EmbeddingVector
    {
        if (empty($vectors)) {
            throw new InvalidArgumentException("Cannot compute average of an empty list of vectors.");
        }

        // Get dimensions from the first vector.
        $firstVector = $vectors[0]->getVector();
        $dim = count($firstVector);
        // Initialize a sum vector with zeros.
        $sumVector = array_fill(0, $dim, 0.0);

        foreach ($vectors as $vector) {
            if (!($vector instanceof EmbeddingVector)) {
                throw new InvalidArgumentException("All elements in the list must be EmbeddingVector instances.");
            }
            $currentVec = $vector->getVector();
            if (count($currentVec) !== $dim) {
                throw new InvalidArgumentException("All vectors must have consistent dimensions for averaging. Expected $dim, got " . count($currentVec));
            }
            // Sum up components of each vector.
            for ($i = 0; $i < $dim; $i++) {
                $sumVector[$i] += $currentVec[$i];
            }
        }

        $numVectors = count($vectors);
        $averageVector = [];
        // Divide sum by the number of vectors to get the average.
        for ($i = 0; $i < $dim; $i++) {
            $averageVector[$i] = $sumVector[$i] / $numVectors;
        }

        return new EmbeddingVector($averageVector, $normalize);
    }

    /**
     * Computes a robust average of a list of vectors by first removing outliers.
     *
     * This method first calculates a standard average, then identifies and removes
     * the vectors that are farthest from this average. It then calculates a new
     * average from the remaining, closer vectors, resulting in a more robust and
     * representative central vector.
     *
     * @param array<EmbeddingVector> $vectors An array containing EmbeddingVector objects.
     * @param float $percentile The percentile of vectors to keep, as a float between 0 and 1 (e.g., 0.8 for 80%).
     * @return EmbeddingVector A new EmbeddingVector instance representing the robust average.
     * @throws InvalidArgumentException If the input list is empty, contains non-EmbeddingVector elements,
     * or if the percentile is out of a valid range.
     */
    public static function robustAverage(array $vectors, float $percentile = 0.8, $normalize = false): EmbeddingVector
    {
        if (empty($vectors)) {
            throw new InvalidArgumentException("Cannot compute robust average of an empty list of vectors.");
        }
        if ($percentile <= 0.0 || $percentile > 1.0) {
            throw new InvalidArgumentException("Percentile must be a float between 0 (exclusive) and 1 (inclusive).");
        }

        // 1. Calculate an initial average of all vectors.
        $initialAverage = self::average($vectors, true); //normalize - needed for below distances!

        // 2. Calculate the distance of each vector to the initial average.
        $distances = [];
        foreach ($vectors as $key => $vector) {
            if (!($vector instanceof EmbeddingVector)) {
                throw new InvalidArgumentException("All elements in the list must be EmbeddingVector instances.");
            }
            $distances[$key] = $vector->distance($initialAverage);
        }

        // 3. Sort the distances to find the closest vectors.
        asort($distances);

        // 4. Determine how many vectors to keep based on the percentile.
        $numToKeep = (int) ceil(count($vectors) * $percentile);
        $keysToKeep = array_slice(array_keys($distances), 0, $numToKeep);

        // 5. Create a new list with only the closest vectors.
        $filteredVectors = [];
        foreach ($keysToKeep as $key) {
            $filteredVectors[] = $vectors[$key];
        }

        // 6. Calculate the final average from the filtered list.
        return self::average($filteredVectors, $normalize);
    }


    /**
     * Performs a K-Nearest Neighbors (KNN) search.
     *
     * Finds the K nearest vectors to a given search vector from a list of candidate vectors.
     * The search is performed in-memory.
     *
     * @param EmbeddingVector $searchVector The vector for which to find nearest neighbors.
     * @param array<string, EmbeddingVector|string|array> $candidateVectors An associative array where keys are IDs (e.g., strings)
     * and values are `EmbeddingVector` objects, packed strings, or arrays of floats.
     * @param int|null $k The number of nearest neighbors to return.
     * If `null` or greater than the number of candidates, all candidates
     * will be returned, sorted by distance.
     * @return array<string, float> An associative array where keys are the IDs of the nearest neighbors
     * and values are their cosine differences to the search vector,
     * sorted in ascending order of distance.
     * @throws InvalidArgumentException If the candidate vectors list is empty.
     */
    public static function knn(EmbeddingVector $searchVector, array $candidateVectors, ?int $k = null): array
    {
        if (empty($candidateVectors)) {
            throw new InvalidArgumentException("Candidate vectors list cannot be empty for KNN search.");
        }

        $results = [];
        foreach ($candidateVectors as $id => $candidateData) {
            try {
                // Convert candidate data to EmbeddingVector, normalizing it for distance calculation
                $candidateVector = ($candidateData instanceof EmbeddingVector)
                    ? $candidateData
                    : new EmbeddingVector($candidateData, true); // Normalize automatically

                // Calculate distance using the instance method.
                $distance = $searchVector->distance($candidateVector);
                $results[$id] = $distance;
            } catch (InvalidArgumentException $e) {
                // Catch invalid input or dimension mismatch for a specific candidate and skip it.
                error_log("KNN: Problem with candidate ID '$id': " . $e->getMessage() . " - This candidate will be skipped.");
                continue;
            }
        }

        // Sort the results by distance in ascending order (nearest first).
        asort($results);

        // If K is specified and positive, return only the top K results.
        if ($k !== null && $k > 0) {
            return array_slice($results, 0, $k, true); // Preserve keys
        }

        // Otherwise, return all sorted results.
        return $results;
    }

    /**
     * Performs a Farthest-First Traversal on a list of vectors.
     *
     * This algorithm starts with the first vector in the list and iteratively
     * selects the vector that is farthest from the set of all previously selected
     * vectors. It's useful for creating a diverse, well-spread-out subset.
     *
     * @param array<string|int, EmbeddingVector> $vectors An associative array where keys are IDs and values are EmbeddingVector objects.
     * @return array<string|int> A new array containing the IDs of the vectors in farthest-first order.
     * @throws InvalidArgumentException If the input list is empty or contains non-EmbeddingVector elements.
     */
    public static function farthestFirstTraversal(array $vectors): array
    {
        $count = count($vectors);
        if ($count === 0) {
            return [];
        }
        if ($count === 1) {
            return $vectors;
        }

        // Validate all inputs are EmbeddingVector objects
        foreach ($vectors as $vector) {
            if (!($vector instanceof EmbeddingVector)) {
                throw new InvalidArgumentException("All elements in the list must be EmbeddingVector instances.");
            }
        }

        // Get the ID (key) and the vector for the starting point.
        $startKey = key($vectors);
        $startVector = $vectors[$startKey];
        $result = [$startKey];

        // A temporary array to keep track of unselected vectors.
        $unselected = $vectors;
        unset($unselected[$startKey]);

        // Continue until all vectors have been selected.
        while (!empty($unselected)) {
            $farthestVectorKey = null;
            $maxMinDistance = -1.0;

            // Iterate through all unselected vectors to find the one farthest from the selected set.
            foreach ($unselected as $candidateKey => $candidateVector) {
                $minDistanceToSelected = PHP_FLOAT_MAX;

                // For each candidate, find its minimum distance to any of the already selected vectors.
                foreach ($result as $selectedKey) {
                    $selectedVector = $vectors[$selectedKey]; // Get the full vector from the original list
                    $distance = $candidateVector->distance($selectedVector);
                    $minDistanceToSelected = min($minDistanceToSelected, $distance);
                }

                // If this candidate's minimum distance is the greatest so far, it's the new "farthest".
                if ($minDistanceToSelected > $maxMinDistance) {
                    $maxMinDistance = $minDistanceToSelected;
                    $farthestVectorKey = $candidateKey;
                }
            }

            // Add the farthest vector's key to the result and remove it from the unselected set.
            if ($farthestVectorKey !== null) {
                $result[] = $farthestVectorKey;
                unset($unselected[$farthestVectorKey]);
            }
        }

        return $result;
    }


    /**
     * Calculates the L2 norm (magnitude) of the vector.
     *
     * @return float The magnitude of the vector.
     */
    public function magnitude(): float
    {
        $sumOfSquares = 0.0;
        foreach ($this->vector as $val) {
            $sumOfSquares += $val * $val;
        }
        return sqrt($sumOfSquares);
    }

    /**
     * Helper method to normalize a given vector (array of floats).
     *
     * This method is useful if operations like `subtract` or `average` produce
     * a non-normalized vector that needs to be normalized before being used
     * in `distance` calculations (which assume normalized inputs).
     *
     * @param array $vector The vector (array of floats) to normalize.
     * @return array The L2-normalized vector.
     * @throws InvalidArgumentException If the vector is empty or contains non-numeric values.
     */
    public static function normalizeVector(array $vector): array
    {
        if (empty($vector)) {
            throw new InvalidArgumentException("Cannot normalize an empty vector.");
        }

        $sumOfSquares = 0.0;
        foreach ($vector as $val) {
            if (!is_numeric($val)) {
                throw new InvalidArgumentException("Vector elements must be numeric for normalization.");
            }
            $sumOfSquares += $val * $val;
        }

        $magnitude = sqrt($sumOfSquares);

        // If magnitude is zero, the vector is a zero vector. Return it as is.
        // Normalizing a zero vector is undefined, but returning it as zero is a common practical approach.
        if ($magnitude == 0.0) {
            return $vector;
        }

        $normalizedVector = [];
        foreach ($vector as $val) {
            $normalizedVector[] = $val / $magnitude;
        }

        return $normalizedVector;
    }
}

// --- PHP Idiom for Direct Execution vs. Inclusion ---
// This block will only execute if the file is run directly (e.g., `php your_file.php`)
// and will be skipped if the file is included (e.g., `require 'your_file.php';`.
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Function to pack an array of floats into a binary string
    function packFloats(array $floats): string
    {
        // 'g*' packs floats (IEEE 754 single-precision) in little-endian byte order
        return pack('g*', ...$floats);
    }

    // Function to create a simple normalized vector for demonstration
    function createNormalizedVector(array $data): EmbeddingVector
    {
        $normalizedData = EmbeddingVector::normalizeVector($data);
        return new EmbeddingVector($normalizedData);
    }

    try {
        echo "--- EmbeddingVector Class Demonstration ---\n\n";

        // 1. Create vectors
        $vectorAData = [0.5, 0.8, 0.3, 0.1];
        $vectorBData = [0.6, 0.7, 0.2, 0.15];
        $vectorCData = [-0.4, -0.9, -0.1, -0.2]; // More opposite to A and B
        $vectorDData = [0.55, 0.75, 0.25, 0.12]; // Similar to A and B
        $vectorEData = [0.1, 0.2, 0.9, 0.8]; // A different "cluster"
        $vectorFData = [0.0, 0.1, 0.85, 0.82]; // Similar to E

        // Using the new constructor parameter for normalization
        $vectorA = new EmbeddingVector($vectorAData, true);
        $vectorB = new EmbeddingVector($vectorBData, true);
        $vectorC = new EmbeddingVector($vectorCData, true);
        $vectorD = new EmbeddingVector($vectorDData, true);
        $vectorE = new EmbeddingVector($vectorEData, true);
        $vectorF = new EmbeddingVector($vectorFData, true);


        echo "Vector A: [" . implode(', ', $vectorA->getVector()) . "]\n";
        echo "Vector B: [" . implode(', ', $vectorB->getVector()) . "]\n";
        echo "Vector C: [" . implode(', ', $vectorC->getVector()) . "]\n";
        echo "Vector D: [" . implode(', ', $vectorD->getVector()) . "]\n";
        echo "Vector E: [" . implode(', ', $vectorE->getVector()) . "]\n";
        echo "Vector F: [" . implode(', ', $vectorF->getVector()) . "]\n\n";

        // Test constructor with packed string
        $packedVectorA = packFloats($vectorAData);
        $vectorAFromString = new EmbeddingVector($packedVectorA, true); // Normalize from string
        echo "Vector A (from string, normalized): [" . implode(', ', $vectorAFromString->getVector()) . "]\n\n";


        // 2. Distance Calculation
        echo "--- Distance Calculation (Cosine Difference) ---\n";
        $distAB = $vectorA->distance($vectorB);
        $distAC = $vectorA->distance($vectorC);
        $distAD = $vectorA->distance($vectorD);
        echo "Distance A to B: " . sprintf('%.4f', $distAB) . " (closer to 0 means more similar)\n";
        echo "Distance A to C: " . sprintf('%.4f', $distAC) . " (closer to 2 means more opposite)\n";
        echo "Distance A to D: " . sprintf('%.4f', $distAD) . "\n\n";

        // Test distance with array and string inputs
        echo "Distance A to B (using array input for B): " . sprintf('%.4f', $vectorA->distance($vectorBData)) . "\n";
        echo "Distance A to C (using packed string input for C): " . sprintf('%.4f', $vectorA->distance(packFloats($vectorCData))) . "\n\n";


        // 3. Dot Product (New Method)
        echo "--- Dot Product (Cosine Similarity for normalized vectors) ---\n";
        $dotProductAB = $vectorA->dotProduct($vectorB);
        $dotProductAC = $vectorA->dotProduct($vectorC);
        echo "Dot Product A . B: " . sprintf('%.4f', $dotProductAB) . " (closer to 1 means more similar)\n";
        echo "Dot Product A . C: " . sprintf('%.4f', $dotProductAC) . " (closer to -1 means more opposite)\n\n";


        // 4. Subtraction
        echo "--- Vector Subtraction ---\n";
        $subtractedVector = $vectorA->subtract($vectorC);
        echo "Vector A - Vector C (unnormalized): [" . implode(', ', $subtractedVector->getVector()) . "]\n";
        // Using the new getNormalizedVector() method
        echo "Vector A - Vector C (normalized via getNormalizedVector()): [" . implode(', ', $subtractedVector->getNormalizedVector()) . "]\n\n";

        // Test subtraction with array and string inputs
        $subtractedVectorFromArray = $vectorA->subtract($vectorBData);
        echo "Vector A - Vector B (using array input for B, unnormalized): [" . implode(', ', $subtractedVectorFromArray->getVector()) . "]\n";
        $subtractedVectorFromString = $vectorA->subtract(packFloats($vectorCData));
        echo "Vector A - Vector C (using packed string input for C, unnormalized): [" . implode(', ', $subtractedVectorFromString->getVector()) . "]\n\n";

        // Test subtraction with auto-normalization
        $subtractedVectorNormalized = $vectorA->subtract($vectorC, true);
        echo "Vector A - Vector C (auto-normalized): [" . implode(', ', $subtractedVectorNormalized->getVector()) . "]\n";
        echo "Distance from A to (A-C normalized): " . sprintf('%.4f', $vectorA->distance($subtractedVectorNormalized)) . "\n\n";


        // 5. Addition (New Method)
        echo "--- Vector Addition ---\n";
        $addedVector = $vectorA->add($vectorB);
        echo "Vector A + Vector B (unnormalized): [" . implode(', ', $addedVector->getVector()) . "]\n";
        $addedVectorNormalized = $vectorA->add($vectorB, true);
        echo "Vector A + Vector B (auto-normalized): [" . implode(', ', $addedVectorNormalized->getVector()) . "]\n";
        echo "Distance from A to (A+B normalized): " . sprintf('%.4f', $vectorA->distance($addedVectorNormalized)) . "\n\n";


        // 6. Average
        echo "--- Average Vector ---\n";
        $vectorsToAverage = [$vectorA, $vectorB, $vectorD];
        $averageVector = EmbeddingVector::average($vectorsToAverage);
        echo "Average of A, B, D (unnormalized): [" . implode(', ', $averageVector->getVector()) . "]\n";
        // Using the new getNormalizedVector() method for distance comparisons
        $normalizedAverageData = $averageVector->getNormalizedVector();
        $normalizedAverage = new EmbeddingVector($normalizedAverageData);
        echo "Average of A, B, D (normalized): [" . implode(', ', $normalizedAverage->getVector()) . "]\n";
        echo "Distance from normalized average to Vector A: " . sprintf('%.4f', $normalizedAverage->distance($vectorA)) . "\n\n";


        // 7. KNN (K-Nearest Neighbors)
        echo "--- K-Nearest Neighbors (KNN) ---\n";
        $candidateVectors = [
            'doc1' => $vectorA,
            'doc2' => $vectorB,
            'doc3' => $vectorC,
            'doc4' => $vectorD,
            'doc5' => [0.1, 0.2, 0.9, 0.8], // Array input
            'doc6' => packFloats([0.52, 0.78, 0.28, 0.11]), // Packed string input
            'doc7' => new EmbeddingVector([0.9, 0.1, 0.2, 0.3], true) // EmbeddingVector object
        ];

        $searchQueryVector = new EmbeddingVector([0.5, 0.7, 0.2, 0.1], true); // Similar to A, B, D, normalized on creation

        echo "Search Query Vector: [" . implode(', ', $searchQueryVector->getVector()) . "]\n\n";

        echo "KNN (top 3 results):\n";
        $knnResults = EmbeddingVector::knn($searchQueryVector, $candidateVectors, 3);
        foreach ($knnResults as $id => $distance) {
            echo "  - ID: $id, Distance: " . sprintf('%.4f', $distance) . "\n";
        }
        echo "\n";

        echo "KNN (all results, sorted):\n";
        $allKnnResults = EmbeddingVector::knn($searchQueryVector, $candidateVectors);
        foreach ($allKnnResults as $id => $distance) {
            echo "  - ID: $id, Distance: " . sprintf('%.4f', $distance) . "\n";
        }
        echo "\n";


        // 8. Farthest-First Traversal (New Method)
        echo "--- Farthest-First Traversal ---\n";
        $allVectors = [
            'A' => $vectorA,
            'B' => $vectorB,
            'C' => $vectorC,
            'D' => $vectorD,
            'E' => $vectorE,
            'F' => $vectorF
        ];

        $traversedKeys = EmbeddingVector::farthestFirstTraversal($allVectors);

        echo "Original vector order (by type A, B, C, D, E, F):\n";
        echo "  - Start: A\n";
        echo "  - Similar: B, D\n";
        echo "  - Opposite: C\n";
        echo "  - Different cluster: E, F\n\n";

        echo "Farthest-first traversal order (starting with Vector A):\n";
        $index = 0;
        foreach ($traversedKeys as $key) {
            echo "  - " . ($index + 1) . ". Vector $key\n";
            $index++;
        }
        echo "\n";


        // 9. Magnitude (New Method)
        echo "--- Vector Magnitude (L2 Norm) ---\n";
        echo "Magnitude of Vector A: " . sprintf('%.4f', $vectorA->magnitude()) . "\n";
        echo "Magnitude of (A - C unnormalized): " . sprintf('%.4f', $subtractedVector->magnitude()) . "\n";
        echo "Magnitude of (A - C normalized): " . sprintf('%.4f', $subtractedVectorNormalized->magnitude()) . "\n\n";


        echo "--- Error Handling Examples ---\n";
        try {
            new EmbeddingVector("invalid_packed_string");
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error: " . $e->getMessage() . "\n";
        }

        try {
            new EmbeddingVector([1, 2, 'not_a_number']);
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error: " . $e->getMessage() . "\n";
        }

        try {
            $v1 = new EmbeddingVector([1, 2]);
            $v2 = new EmbeddingVector([1, 2, 3]);
            $v1->distance($v2);
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error: " . $e->getMessage() . "\n";
        }

        try {
            EmbeddingVector::average([]);
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error: " . $e->getMessage() . "\n";
        }

        try {
            EmbeddingVector::knn($vectorA, []);
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error: " . $e->getMessage() . "\n";
        }

        // Test invalid input to distance/subtract/knn
        try {
            $vectorA->distance(new stdClass());
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error (invalid distance input): " . $e->getMessage() . "\n";
        }

        try {
            $vectorA->subtract(new stdClass());
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error (invalid subtract input): " . $e->getMessage() . "\n";
        }

        try {
            EmbeddingVector::knn($vectorA, ['bad_key' => new stdClass()]);
        } catch (InvalidArgumentException $e) {
            echo "Caught expected error (invalid KNN candidate): " . $e->getMessage() . "\n";
        }


    } catch (Exception $e) {
        echo "An unexpected error occurred: " . $e->getMessage() . "\n";
    }
}

