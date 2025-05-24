    <?php
    require '../../Config/database.php';
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents('php://input'), true);
    $uid = $data['uid'] ?? null;
    $appId = $data['appId'] ?? null;

    if (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'Missing user ID']);
        exit;
    }

    try {
        $conn->beginTransaction();        $sql = "UPDATE tracking 
                SET trackingStatus = 'Not Tracking',
                    trackingStatusType = 'Left',
                    trackingEnd = NOW()
                WHERE UID = :uid 
                AND trackingStatus = 'Ongoing'
                AND trackingEnd IS NULL";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([':uid' => $uid]);

        $trackingEnd = $conn->query("SELECT NOW() as time")->fetch()['time'];

        if ($appId) {
            $updateAppSql = "UPDATE Appointments 
                            SET status = 'Completed'
                            WHERE appID = :appId";
            $updateAppStmt = $conn->prepare($updateAppSql);
            $updateAppStmt->execute([':appId' => $appId]);
        }

        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'trackingEnd' => $trackingEnd
        ]);

    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
