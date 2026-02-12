<?php
$dsn = "mysql:host=127.0.0.1;dbname=fitsense";
$user = "root";
$pass = "root";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully\n";
    
    $sqls = [
        "ALTER TABLE questionnaire ADD titre VARCHAR(255) DEFAULT NULL, ADD options JSON DEFAULT NULL, ADD type VARCHAR(20) DEFAULT 'response' NOT NULL, ADD coach_id INT DEFAULT NULL, CHANGE date_soumission date_soumission DATETIME DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL",
        "ALTER TABLE questionnaire ADD CONSTRAINT FK_7A64DAF3C105691 FOREIGN KEY (coach_id) REFERENCES user (id)",
        "CREATE INDEX IDX_7A64DAF3C105691 ON questionnaire (coach_id)",
        "CREATE TABLE questionnaire_workout (questionnaire_id INT NOT NULL, workout_id INT NOT NULL, INDEX IDX_F8C8A3DBCE07E8FF (questionnaire_id), INDEX IDX_F8C8A3DBA6CCCFC9 (workout_id), PRIMARY KEY(questionnaire_id, workout_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB",
        "ALTER TABLE questionnaire_workout ADD CONSTRAINT FK_F8C8A3DBCE07E8FF FOREIGN KEY (questionnaire_id) REFERENCES questionnaire (id) ON DELETE CASCADE",
        "ALTER TABLE questionnaire_workout ADD CONSTRAINT FK_F8C8A3DBA6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id) ON DELETE CASCADE",
        "ALTER TABLE questionnaire DROP FOREIGN KEY FK_7A64DAFA6CCCFC9",
        "DROP INDEX IDX_7A64DAFA6CCCFC9 ON questionnaire",
        "ALTER TABLE questionnaire DROP workout_id"
    ];
    
    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            echo "Success: $sql\n";
        } catch (Exception $e) {
            echo "Skipped/Error: $sql -> " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
