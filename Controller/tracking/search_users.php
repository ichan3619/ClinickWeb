<?php
require '../../Config/database.php';

if(isset($_POST['search'])) {
    $search = $_POST['search'];
    
    try {
        $query = "SELECT 
                    u.UID,
                    u.schoolID as school_no,
                    CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, '')) as name,
                    t.appID,
                    t.trackingStatusType as status,
                    t.trackingStart,
                    t.trackingEnd,
                    t.trackingStatus as tracking_status,
                    (
                        SELECT trackingEnd
                        FROM tracking t2 
                        WHERE t2.UID = u.UID 
                        AND t2.trackingEnd IS NOT NULL
                        AND t2.trackingID != COALESCE(t.trackingID, 0)
                        ORDER BY t2.trackingEnd DESC 
                        LIMIT 1
                    ) as last_seen
                FROM userInfo u
                LEFT JOIN tracking t ON u.UID = t.UID AND t.trackingStatus = 'Ongoing'
                WHERE CONCAT(COALESCE(u.fname, ''), ' ', COALESCE(u.lname, '')) LIKE :search
                ORDER BY 
                    CASE WHEN t.trackingStatus = 'Ongoing' THEN 0 ELSE 1 END,
                    t.trackingStart DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute(['search' => "%$search%"]);
        
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $isTracking = $row['tracking_status'] === 'Ongoing';
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['school_no']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . ($isTracking ? htmlspecialchars($row['trackingStart']) : '-') . "</td>";
                echo "<td>" . ($isTracking && !empty($row['trackingEnd']) ? htmlspecialchars($row['trackingEnd']) : '-') . "</td>";
                echo "<td>" . (!empty($row['status']) ? htmlspecialchars($row['status']) : 'Not In Clinic') . "</td>";
                echo "<td>";
                
                if ($isTracking) {
                    echo "<button class='tracking-btn tracking-active'>Tracking...</button>";
                    echo "<button class='tracking-btn end-tracking' 
                            data-uid='" . $row['UID'] . "' 
                            data-appid='" . ($row['appID'] ?? 'null') . "'>
                            End Tracking
                          </button>";
                    if (!empty($row['trackingStart'])) {
                        echo "<br><span class='tracking-info' data-start='" . $row['trackingStart'] . "'>Started: " . $row['trackingStart'] . "</span>";
                    }
                } else {
                    echo "<button class='tracking-btn start-tracking' 
                            data-uid='" . $row['UID'] . "' 
                            data-appid='" . ($row['appID'] ?? 'null') . "'>
                            Start Tracking
                          </button>";
                }
                
                echo "</td>";
                $lastSeen = !empty($row['last_seen']) ? date('Y-m-d H:i:s', strtotime($row['last_seen'])) : '-';
                echo "<td>" . htmlspecialchars($lastSeen) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7' style='text-align: center;'>No records found</td></tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='7' style='text-align: center; color: red;'>Error: " . $e->getMessage() . "</td></tr>";
    }
}
?>
