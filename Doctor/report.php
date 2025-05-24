<?php
require '../Config/database.php';

$span  = $_GET['span'] ?? 'day';
$today = date('Y-m-d');

switch ($span) {
  case 'week':
    $start = date('Y-m-d', strtotime('monday this week'));
    $end   = date('Y-m-d', strtotime($start . ' +1 week'));
    break;

  case 'month':
    $start = date('Y-m-01');
    $end   = date('Y-m-d', strtotime($start . ' +1 month'));
    break;

  default:
    $start = $today;
    $end   = date('Y-m-d', strtotime($today . ' +1 day'));
    break;
}

$sql = "
  SELECT 
    u.schoolID AS school_no,
    CONCAT(u.fname,' ',u.lname) AS name,
    dept.deptName AS department,
    a.consultDate,
    a.mode
  FROM appointments a
  JOIN userInfo u    ON a.UID    = u.UID
  JOIN departments dept ON u.deptID = dept.deptID
  WHERE a.status    = 'Prescribed'
    AND DATE(a.consultDate) >= :start
    AND DATE(a.consultDate)  < :end
  ORDER BY a.consultDate ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([':start' => $start, ':end' => $end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Prescribed Report: <?= htmlspecialchars(ucfirst($span)) ?></title>
  <style>
    body { font-family: sans-serif; padding: 1rem; }
    h1 { text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top:1rem; }
    th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
  </style>
</head>
<body onload="window.print()">
  <h1>Prescribed Consultations — <?= ucfirst($span) ?></h1>
  <p>From <?= htmlspecialchars($start) ?> to <?= htmlspecialchars(date('Y-m-d',strtotime($end)-1)) ?></p>
  <?php if (count($rows)===0): ?>
    <p>No records found.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>School No.</th>
          <th>Name</th>
          <th>Department</th>
          <th>Consult Date</th>
          <th>Mode</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['school_no']) ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><?= htmlspecialchars($r['department']) ?></td>
            <td><?= htmlspecialchars($r['consultDate']) ?></td>
            <td><?= htmlspecialchars($r['mode']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
