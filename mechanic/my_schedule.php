<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

$mechanic_id = $_SESSION['id'];
$success = $error = '';

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return round($diff/60) . " minutes ago";
    if ($diff < 86400) return round($diff/3600) . " hours ago";
    if ($diff < 604800) return round($diff/86400) . " days ago";
    if ($diff < 2592000) return round($diff/604800) . " weeks ago";
    if ($diff < 31536000) return round($diff/2592000) . " months ago";
    return round($diff/31536000) . " years ago";
}

$current_date = date('Y-m-d');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date       = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];
    $slot_id    = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : null;

    // validations
    if (strtotime("$date $start_time") < time()) {
        $error = "You cannot add or update a slot that is in the past.";
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $error = "Start time must be earlier than end time.";
    } elseif (strtotime($start_time) < strtotime('04:00') || strtotime($end_time) > strtotime('23:00')) {
        $error = "Service can be scheduled only between 4:00 AM and 11:00 PM.";
    } else {
        // overlap check
        $sql = "SELECT * FROM mechanic_slots WHERE mechanic_id=? AND date=? AND status='active'
                AND ((start_time< ? AND end_time> ?) OR (start_time< ? AND end_time> ?) OR (start_time>= ? AND end_time<= ?))";
        if ($slot_id) $sql .= " AND id<> ?";
        $check = $conn->prepare($sql);
        if ($slot_id) {
            $check->bind_param("isssssssi",
                $mechanic_id, $date,
                $end_time, $end_time,
                $start_time, $start_time,
                $start_time, $end_time,
                $slot_id
            );
        } else {
            $check->bind_param("isssssss",
                $mechanic_id, $date,
                $end_time, $end_time,
                $start_time, $start_time,
                $start_time, $end_time
            );
        }
        $check->execute();
        $overlap = $check->get_result()->num_rows;
        $check->close();

        if ($overlap) {
            $error = "This time slot overlaps with an existing slot.";
        } else {
            if ($slot_id) {
                $stmt = $conn->prepare(
                    "UPDATE mechanic_slots 
                     SET date=?, start_time=?, end_time=?, updated_at=NOW() 
                     WHERE id=? AND mechanic_id=?"
                );
                $stmt->bind_param("sssii", $date, $start_time, $end_time, $slot_id, $mechanic_id);
                $success = "Time slot updated successfully.";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO mechanic_slots (mechanic_id, date, start_time, end_time)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("isss", $mechanic_id, $date, $start_time, $end_time);
                $success = "Time slot added successfully.";
            }
            $stmt->execute();
            $stmt->close();
            if ($slot_id) {
                header("Location: my_schedule.php?updated=1");
                exit();
            }
        }
    }
}

// Delete
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $slotQ = $conn->prepare(
        "SELECT date, start_time 
         FROM mechanic_slots WHERE id=? AND mechanic_id=?"
    );
    $slotQ->bind_param("ii", $id, $mechanic_id);
    $slotQ->execute();
    $slot = $slotQ->get_result()->fetch_assoc();
    $slotQ->close();

    if ($slot && strtotime("{$slot['date']} {$slot['start_time']}") > time()) {
        $upd = $conn->prepare(
          "UPDATE mechanic_slots 
           SET status='deleted', updated_at=NOW() 
           WHERE id=? AND mechanic_id=?"
        );
        $upd->bind_param("ii", $id, $mechanic_id);
        $upd->execute();
        $upd->close();
        $success = "Slot deleted successfully.";
    } else {
        $error = "Cannot delete a past slot.";
    }
}

// Edit fetch
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $eq = $conn->prepare(
      "SELECT * FROM mechanic_slots 
       WHERE id=? AND mechanic_id=? AND status='active'"
    );
    $eq->bind_param("ii", $eid, $mechanic_id);
    $eq->execute();
    $edit_data = $eq->get_result()->fetch_assoc();
    $eq->close();

    // prevent editing a past slot
    if (strtotime("{$edit_data['date']} {$edit_data['start_time']}") < time()) {
        unset($edit_data);
        $error = "Cannot update a past slot.";
    }
}

// Fetch active slots
$all = $conn->prepare(
  "SELECT * FROM mechanic_slots 
   WHERE mechanic_id=? AND status='active'
   ORDER BY date, start_time"
);
$all->bind_param("i", $mechanic_id);
$all->execute();
$slot_result = $all->get_result();
$all->close();
if (isset($_GET['deleted'])) {
    $success = "Slot deleted successfully.";
}
if (isset($_GET['updated'])) {
    $success = "Time slot updated successfully!";
}
if (isset($_GET['added'])) {
    $success = "Time slot added successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Schedule</title>
  <link rel="stylesheet" href="../dashboard.css">
  <style>
    body { margin:0; font-family:'Segoe UI',sans-serif; background:#f0f2f5; }
    header { background:#2c3e50; color:#fff; padding:15px; display:flex; align-items:center; }
    #toggleBtn{background:#34495e;color:#fff;border:none;padding:8px 14px;border-radius:4px;cursor:pointer;margin-right:15px;}
    #toggleBtn:hover{background:#17a2b8;}
    .main-content{padding:30px;}
    form{background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);margin-bottom:30px;}
    form input, form button{width:100%;padding:10px;margin-top:10px;border-radius:5px;border:1px solid #ccc;}
    form button{background:#00aaff;color:#fff;border:none;cursor:pointer;}
    .message{padding:12px;margin-bottom:20px;border-radius:5px;}
    .success-message{background:#d4edda;color:#155724;}
    .error-message{background:#f8d7da;color:#721c24;}
    .section-title{margin-top:30px;font-size:1.3rem;color:#2c3e50;border-bottom:2px solid #ddd;padding-bottom:5px;}
    .slot-card{background:#fff;padding:15px;border-radius:10px;box-shadow:0 0 8px rgba(0,0,0,0.05);
               display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}
    .slot-card.past{opacity:0.6;filter:grayscale(60%);}
    .slot-details{flex:1;}
    .slot-actions a{display:inline-block;width:36px;height:36px;margin-left:10px;
                    border-radius:50%;background:#e0f0ff;position:relative;text-align:center;transition:0.3s;}
    .slot-actions a::before{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);}
    .slot-actions .edit::before{content:'✏️';}
    .slot-actions .delete::before{content:'🗑️';}
    .slot-actions a:hover{background:#d0e9ff;transform:scale(1.1);}
    .time-ago{margin-left:8px;color:#555;}
  </style>
</head>
<body>
<?php include '../sidebar.php'; ?>
<div class="main-content">
  <header>
    <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
    <h1>Manage My Availability</h1>
  </header>

  <?php if($success): ?>
    <div class="message success-message">✅ <?= $success ?></div>
  <?php elseif($error): ?>
    <div class="message error-message">❌ <?= $error ?></div>
  <?php endif; ?>

  <!-- Add / Update Form -->
  <form method="POST">
    <h2><?= isset($edit_data) ? 'Update Slot' : 'Add New Time Slot' ?></h2>
    <?php if(isset($edit_data)): ?>
      <input type="hidden" name="slot_id" value="<?= $edit_data['id'] ?>">
    <?php endif; ?>
    <label>Date (tomorrow onward)</label>
    <input type="date" name="date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
           value="<?= $edit_data['date'] ?? '' ?>">
    <label>Start Time</label>
    <input type="time" name="start_time" required value="<?= $edit_data['start_time'] ?? '' ?>">
    <label>End Time</label>
    <input type="time" name="end_time" required value="<?= $edit_data['end_time'] ?? '' ?>">
    <button type="submit"><?= isset($edit_data) ? 'Update Slot' : 'Add Slot' ?></button>
  </form>

  <!-- Today's Slots -->
  <h2 class="section-title">Today's Slots</h2>
  <?php
    $slot_result->data_seek(0);
    $found=false;
    while($s=$slot_result->fetch_assoc()):
      if($s['date']=== $current_date):
        $found=true;
        $past = strtotime("{$s['date']} {$s['end_time']}") < time();
  ?>
    <div class="slot-card <?= $past?'past':'' ?>">
      <div class="slot-details">
        🕒 <?= date('h:i A',strtotime($s['start_time'])) ?> – <?= date('h:i A',strtotime($s['end_time'])) ?>
        <small class="time-ago" data-timestamp="<?= $s['updated_at'] ?>">Just now</small>
      </div>
      <div class="slot-actions">
        <?php if(!$past): ?>
          <a href="?edit_id=<?= $s['id'] ?>" class="edit"></a>
          <a href="?delete_id=<?= $s['id'] ?>" class="delete" onclick="return confirm('Are you sure?')"></a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; endwhile;
    if(!$found) echo '<p>No slots for today.</p>';
  ?>

  <!-- Upcoming Slots -->
  <h2 class="section-title">Upcoming Slots</h2>
  <?php
    $slot_result->data_seek(0);
    $found=false;
    while($s=$slot_result->fetch_assoc()):
      if($s['date'] > $current_date):
        $found=true;
  ?>
    <div class="slot-card">
      <div class="slot-details">
        📅 <?= date('l, d M Y',strtotime($s['date'])) ?> |
        🕒 <?= date('h:i A',strtotime($s['start_time'])) ?> – <?= date('h:i A',strtotime($s['end_time'])) ?>
        <small class="time-ago" data-timestamp="<?= $s['updated_at'] ?>">Just now</small>
      </div>
      <div class="slot-actions">
        <a href="?edit_id=<?= $s['id'] ?>" class="edit"></a>
        <a href="?delete_id=<?= $s['id'] ?>" class="delete" onclick="return confirm('Are you sure?')"></a>
      </div>
    </div>
  <?php endif; endwhile;
    if(!$found) echo '<p>No upcoming slots.</p>';
  ?>

  <!-- Past Slots -->
  <h2 class="section-title">Past Slots</h2>
  <?php
    $slot_result->data_seek(0);
    $found=false;
    while($s=$slot_result->fetch_assoc()):
      if(strtotime("{$s['date']} {$s['end_time']}") < time()):
        $found=true;
  ?>
    <div class="slot-card past">
      <div class="slot-details">
        📅 <?= date('l, d M Y',strtotime($s['date'])) ?> |
        🕒 <?= date('h:i A',strtotime($s['start_time'])) ?> – <?= date('h:i A',strtotime($s['end_time'])) ?>
        <small class="time-ago" data-timestamp="<?= $s['updated_at'] ?>">Just now</small>
      </div>
      <div class="slot-actions"><em>⏳ Slot passed</em></div>
    </div>
  <?php endif; endwhile;
    if(!$found) echo '<p>No past slots.</p>';
  ?>
</div>

<script>
// sidebar
function toggleSidebar(){
  document.querySelector('.sidebar').classList.toggle('active');
}

// turn "YYYY-MM-DD HH:MM:SS" into Date
function parseTimestamp(ts) {
  let [d,t] = ts.split(' ');
  return new Date(d+'T'+t);
}

// humanize diff
function timeAgoJS(d){
  let diff = Math.floor((Date.now() - d.getTime())/1000);
  if(diff<60) return 'Just now';
  if(diff<3600) return Math.floor(diff/60)+' minutes ago';
  if(diff<86400) return Math.floor(diff/3600)+' hours ago';
  if(diff<604800) return Math.floor(diff/86400)+' days ago';
  if(diff<2592000) return Math.floor(diff/604800)+' weeks ago';
  if(diff<31536000) return Math.floor(diff/2592000)+' months ago';
  return Math.floor(diff/31536000)+' years ago';
}

// refresh all
function refreshTimeAgo(){
  document.querySelectorAll('.time-ago').forEach(el=>{
    let ts=el.dataset.timestamp;
    let d=parseTimestamp(ts);
    el.textContent='⏱ '+timeAgoJS(d);
  });
}

refreshTimeAgo();
setInterval(refreshTimeAgo,60000);
</script>
</body>
</html>
