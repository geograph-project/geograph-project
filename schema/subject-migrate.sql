RENAME TABLE subjects TO subjects_old;

CREATE TABLE subjects (
    subject_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject VARCHAR(64) NOT NULL,
    maincontext varchar(128) default null,
    concept varchar(64) default null,
    PRIMARY KEY (subject_id),
    UNIQUE KEY uk_subject (subject)
);

CREATE TABLE subject_embedding (
    analysis_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_id INT UNSIGNED NOT NULL,
    model VARCHAR(10) NOT NULL,
    embeddings VARBINARY(4096) NULL,
    cosine_cnt MEDIUMINT UNSIGNED NULL,
    cosine_min FLOAT NOT NULL,
    cosine_max FLOAT NOT NULL,
    cosine_std FLOAT NOT NULL,
    cosine_avg FLOAT NOT NULL,
    PRIMARY KEY (analysis_id),
    UNIQUE KEY uk_model_subject (subject_id, model),
    FOREIGN KEY (subject_id)
        REFERENCES subjects (subject_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);


INSERT INTO subjects (subject, maincontext, concept)
SELECT subject, maincontext, concept
FROM subjects_old
ORDER BY subject ASC;

-- first preserve the mpnet embedding! (doesnt have any stats!)
INSERT INTO subject_embedding (
    subject_id,
    model,
    embeddings,
    cosine_cnt,
    cosine_min,
    cosine_max,
    cosine_std,
    cosine_avg
)
SELECT
    s.subject_id,
    'mpnet' AS model,
    so.embeddings,
    NULL,
    0,
    0,
    0,
    0
FROM
    subjects_old so
JOIN
    subjects s ON so.subject = s.subject
WHERE so.embeddings IS NOT NULL AND LENGTH(embeddings) = 3072;

-- then preserve the clip stats, its NOT clip embedding
INSERT INTO subject_embedding (
    subject_id,
    model,
    embeddings,
    cosine_cnt,
    cosine_min,
    cosine_max,
    cosine_std,
    cosine_avg
)
SELECT
    s.subject_id,
    'clip' AS model,
    NULL AS embeddings,
    so.clipcnt,
    so.clipmin,
    so.clipmax,
    so.clipstd,
    so.clipavg
FROM
    subjects_old so
JOIN
    subjects s ON so.subject = s.subject WHERE so.clipcnt IS NOT NULL;


SELECT
    (SELECT COUNT(*) FROM subjects_old WHERE embeddings IS NOT NULL AND LENGTH(embeddings) = 3072) AS old_mpnet_count,
    (SELECT COUNT(*) FROM subject_embedding WHERE model = 'mpnet') AS new_mpnet_count;
--Expected Result: Both counts should be equal.

SELECT
    (SELECT COUNT(*) FROM subjects_old WHERE clipcnt IS NOT NULL) AS old_clip_stats_count,
    (SELECT COUNT(*) FROM subject_embedding WHERE model = 'clip') AS new_clip_stats_count;
--Expected Result: Both counts should be equal.

SELECT
    (SELECT COUNT(DISTINCT subject) FROM subjects_old) AS old_subject_count,
    (SELECT COUNT(*) FROM subjects) AS new_subject_count;
--Expected Result: Both counts should be equal.


+-------------+-----------------------+------+-----+---------+-------+
| Field       | Type                  | Null | Key | Default | Extra |
+-------------+-----------------------+------+-----+---------+-------+
| subject     | varchar(64)           | NO   | PRI | NULL    |       |
| maincontext | varchar(128)          | YES  |     | NULL    |       |
| concept     | varchar(64)           | YES  |     | NULL    |       |
| embeddings  | varbinary(3072)       | YES  |     | NULL    |       |
| clipcnt     | mediumint(8) unsigned | YES  |     | NULL    |       |
| clipmin     | float                 | NO   |     | NULL    |       |
| clipmax     | float                 | NO   |     | NULL    |       |
| clipstd     | float                 | NO   |     | NULL    |       |
| clipavg     | float                 | NO   |     | NULL    |       |
+-------------+-----------------------+------+-----+---------+-------+
//                //actully we CANT use the empbedding, as that is mpnet!, need clip to use with gridimage_embedding!!? so lookup clip emebedings using API when building these stats

