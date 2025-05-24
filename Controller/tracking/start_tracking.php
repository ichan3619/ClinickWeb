<?php
require '../../Config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$uid = $data['uid'] ?? null;
$appId = $data['appId'] ?? null;

try {
    $conn->beginTransaction();    $updateSql = "UPDATE tracking 
                  SET trackingStatus = 'Not Tracking',
                      trackingStatusType = 'Left',
                      trackingEnd = NOW()
                  WHERE UID = :uid 
                  AND trackingStatus = 'Ongoing'
                  AND trackingEnd IS NULL";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->execute([':uid' => $uid]);
    $insertSql = "INSERT INTO tracking 
                  (UID, appID,  trackingStatus, trackingStatusType, trackingStart) 
                  VALUES 
                  (:uid, :appId, 'Ongoing', 'In Clinic', NOW())";
    
    $consultType = 'Checkup';
    if ($appId) {
        $typeStmt = $conn->prepare("SELECT consultType FROM Appointments WHERE appID = ?");
        $typeStmt->execute([$appId]);
        if ($row = $typeStmt->fetch()) {
            $consultType = $row['consultType'];
        }
    }    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([
        ':uid' => $uid,
        ':appId' => $appId
    ]);

    $trackingStart = $conn->query("SELECT NOW() as time")->fetch()['time'];

    $conn->commit();
    
    echo json_encode([
        'status' => 'success', 
        'trackingStart' => $trackingStart
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
