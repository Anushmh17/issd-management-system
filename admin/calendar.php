<?php
// =====================================================
// LEARN Management - Admin: Full Calendar
// admin/calendar.php
// =====================================================
define('PAGE_TITLE', 'Academic Calendar');
require_once dirname(__DIR__) . '/backend/config.php';
require_once dirname(__DIR__) . '/backend/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

// --- DATA SUMMARY ---
$today = date('Y-m-d');
$todayLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE DATE(next_followup_datetime) = '$today'")->fetchColumn();
$todayStus  = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE DATE(next_follow_up) = '$today'")->fetchColumn();
$todayPays  = (int)$pdo->query("SELECT COUNT(*) FROM student_payments WHERE DATE(next_due_date) = '$today'")->fetchColumn();

// --- AJAX EVENT FEED ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    $start = $_GET['start'] ?? '';
    $end = $_GET['end'] ?? '';

    $events = [];

    // 1. Lead Follow-ups
    $stmt1 = $pdo->prepare("SELECT id, name, next_followup_datetime as start, notes FROM leads WHERE next_followup_datetime BETWEEN ? AND ?");
    $stmt1->execute([$start, $end]);
    while($row = $stmt1->fetch()) {
        $events[] = [
            'id' => 'lead_' . $row['id'],
            'title' => '📞 Lead: ' . $row['name'],
            'start' => $row['start'],
            'color' => '#f43f5e',
            'extendedProps' => ['type' => 'Lead', 'desc' => $row['notes'], 'link' => BASE_URL . '/admin/leads/index.php?highlight_id=' . $row['id']]
        ];
    }

    // 2. Student Follow-ups
    $stmt2 = $pdo->prepare("SELECT id, full_name, next_follow_up as start, follow_up_note FROM students WHERE next_follow_up BETWEEN ? AND ?");
    $stmt2->execute([$start, $end]);
    while($row = $stmt2->fetch()) {
        $events[] = [
            'id' => 'stu_' . $row['id'],
            'title' => '👥 Stu: ' . $row['full_name'],
            'start' => $row['start'],
            'color' => '#6366f1',
            'extendedProps' => ['type' => 'Student', 'desc' => $row['follow_up_note'], 'link' => BASE_URL . '/admin/students/index.php?highlight_id=' . $row['id']]
        ];
    }

    // 3. Payments
    $stmt3 = $pdo->prepare("SELECT sp.id, s.full_name, sp.next_due_date as start, c.course_name FROM student_payments sp JOIN students s ON sp.student_id = s.id JOIN courses c ON sp.course_id = c.id WHERE sp.next_due_date BETWEEN ? AND ?");
    $stmt3->execute([$start, $end]);
    while($row = $stmt3->fetch()) {
        $events[] = [
            'id' => 'pay_' . $row['id'],
            'title' => '💰 Pay: ' . $row['full_name'],
            'start' => $row['start'],
            'color' => '#10b981',
            'extendedProps' => ['type' => 'Payment', 'desc' => $row['course_name'], 'link' => BASE_URL . '/admin/payments/index.php?highlight_id=' . $row['id']]
        ];
    }

    echo json_encode($events);
    exit;
}
$extraCSS = <<<'CSS'
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
<style>
  #calendar-container {
    background: #fff;
    border-radius: 28px;
    padding: 35px;
    box-shadow: 0 15px 50px rgba(0,0,0,0.04);
    border: 1px solid rgba(226, 232, 240, 0.8);
    backdrop-filter: blur(10px);
  }
  
  /* FullCalendar Customization */
  .fc .fc-toolbar-title { 
    font-size: 22px; 
    font-weight: 800; 
    color: #0f172a;
    letter-spacing: -0.5px;
  }
  .fc .fc-button-primary { 
    background: #fff; 
    border: 1px solid #e2e8f0; 
    color: #475569;
    font-weight: 700; 
    text-transform: capitalize; 
    border-radius: 12px; 
    padding: 10px 18px;
    font-size: 13px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .fc .fc-button-primary:hover { 
    background: #f8fafc; 
    color: var(--primary);
    border-color: var(--primary-light);
    transform: translateY(-1px);
  }
  .fc .fc-button-active { 
    background: var(--primary) !important; 
    color: #fff !important;
    border-color: var(--primary) !important;
    box-shadow: 0 4px 12px rgba(91, 78, 250, 0.2);
  }
  
  .fc-event { 
    border: none !important; 
    border-radius: 8px !important; 
    padding: 4px 10px !important; 
    font-weight: 700 !important; 
    font-size: 11px !important;
    cursor: pointer; 
    transition: 0.3s; 
    margin: 2px 0 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }
  .fc-event:hover { 
    transform: scale(1.03) translateY(-1px); 
    filter: brightness(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  
  .fc-daygrid-day-number { font-weight: 800; color: #94a3b8; font-size: 13px; padding: 12px !important; }
  .fc-day-today { background: rgba(91, 78, 250, 0.03) !important; }
  .fc-col-header-cell-cushion { color: #475569; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; text-decoration: none; padding: 15px 0 !important; }
  
  /* Legend Styling */
  .legend-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 700;
    transition: 0.3s;
    border: 1px solid transparent;
  }
  .legend-pill i { font-size: 14px; }
  .lp-leads { background: #fff1f2; color: #e11d48; border-color: #ffe4e6; }
  .lp-students { background: #eef2ff; color: #4338ca; border-color: #e0e7ff; }
  .lp-payments { background: #f0fdf4; color: #15803d; border-color: #dcfce7; }
  
  /* Event Detail Popup */
  .event-popup {
    display: none;
    position: fixed;
    z-index: 10001;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 380px;
    background: #fff;
    border-radius: 28px;
    padding: 30px;
    box-shadow: 0 25px 60px -12px rgba(15, 23, 42, 0.3);
    animation: zoomIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid rgba(226, 232, 240, 0.5);
  }
  @keyframes zoomIn { from { opacity:0; transform: translate(-50%, -45%) scale(0.95); } to { opacity:1; transform: translate(-50%, -50%) scale(1); } }
  .popup-backdrop {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
    z-index: 10000;
  }
  .event-popup, .day-popup {
    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 420px; background: #fff; border-radius: 32px; padding: 35px;
    box-shadow: 0 25px 80px rgba(15, 23, 42, 0.25);
    z-index: 10001; display: none;
    border: 1px solid rgba(226, 232, 240, 0.8);
    animation: popupFade 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  @keyframes popupFade { from { opacity: 0; transform: translate(-50%, -45%); } to { opacity: 1; transform: translate(-50%, -50%); } }
  
  .day-event-item {
    padding: 12px 15px; border-radius: 16px; margin-bottom: 10px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    display: flex; align-items: center; gap: 12px; transition: all 0.2s;
    text-decoration: none; color: inherit;
  }
  .day-event-item:hover { background: #fff; border-color: var(--primary); transform: translateX(5px); }
  .day-event-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  
  .legend-pill { cursor: pointer; transition: all 0.2s; opacity: 1; }
  .legend-pill.inactive { opacity: 0.35; filter: grayscale(0.5); transform: scale(0.95); }
  .legend-pill:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
</style>
CSS;

require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/sidebar.php';
?>

<div id="page-content">
  <div class="page-header" style="background:none; padding:0; margin-bottom:30px;">
    <div class="page-header-left">
      <h1 style="font-size:32px; font-weight:900; letter-spacing:-1px; margin-bottom:5px;">Academic Calendar</h1>
      <div class="breadcrumb-custom" style="font-size:13px; font-weight:600; color:#94a3b8;">
        <i class="fas fa-house" style="font-size:12px; margin-right:5px;"></i> Admin 
        <span style="margin:0 8px; opacity:0.5;">/</span> 
        <span style="color:var(--primary);">Operational Timeline</span>
      </div>
    </div>
    <div class="page-header-right">
      <div class="d-flex gap-3">
        <div class="legend-pill lp-leads" data-type="Lead" onclick="toggleCalendarFilter(this)">
            <i class="fas fa-phone-volume"></i> 
            <span>Leads</span>
            <span class="badge bg-white text-danger ms-2" style="font-size:10px;"><?= $todayLeads ?></span>
        </div>
        <div class="legend-pill lp-students" data-type="Student" onclick="toggleCalendarFilter(this)">
            <i class="fas fa-graduation-cap"></i> 
            <span>Students</span>
            <span class="badge bg-white text-primary ms-2" style="font-size:10px;"><?= $todayStus ?></span>
        </div>
        <div class="legend-pill lp-payments" data-type="Payment" onclick="toggleCalendarFilter(this)">
            <i class="fas fa-receipt"></i> 
            <span>Payments</span>
            <span class="badge bg-white text-success ms-2" style="font-size:10px;"><?= $todayPays ?></span>
        </div>
      </div>
    </div>
  </div>

  <div id="calendar-container">
    <div id="calendar"></div>
  </div>
</div>

<!-- Event Detail Popup -->
<div class="popup-backdrop" id="popupBackdrop" onclick="closePopup()"></div>

<div class="event-popup" id="eventPopup">
  <div class="d-flex justify-content-between align-items-center mb-15">
    <div id="popup-type" class="text-uppercase fw-800" style="font-size:10px; letter-spacing:1px;"></div>
    <i class="fas fa-times cursor-pointer text-muted" onclick="closePopup()"></i>
  </div>
  <h3 id="popup-title" class="fw-800" style="font-size:18px; margin-bottom:10px;"></h3>
  <div id="popup-desc" class="text-muted mb-20" style="font-size:13px; line-height:1.5;"></div>
  <div class="d-grid gap-2">
    <a id="popup-link" href="#" class="btn btn-primary rounded-pill fw-700 py-2">Go to Record</a>
    <button onclick="closePopup()" class="btn btn-light rounded-pill fw-700 py-2">Close</button>
  </div>
</div>

<!-- Day Events List Popup -->
<div class="day-popup" id="dayPopup">
  <div class="d-flex justify-content-between align-items-center mb-20">
    <div>
        <div class="text-uppercase fw-800 text-primary" style="font-size:10px; letter-spacing:1.5px; margin-bottom:2px;">Agenda For</div>
        <h3 id="day-popup-date" class="fw-900 m-0" style="font-size:22px; letter-spacing:-0.5px;"></h3>
    </div>
    <div style="width:40px; height:40px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; cursor:pointer;" onclick="closePopup()">
        <i class="fas fa-times text-muted"></i>
    </div>
  </div>
  <div id="day-events-list" style="max-height:350px; overflow-y:auto; padding-right:5px;">
    <!-- Items dynamically injected -->
  </div>
  <div class="mt-20 pt-15 border-top text-center">
    <button onclick="closePopup()" class="btn btn-light rounded-pill fw-700 px-30">Close Agenda</button>
  </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<script>
let activeFilters = ['Lead', 'Student', 'Payment'];
let calendar;

document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    navLinks: true,
    navLinkDayClick: function(date, jsEvent) {
      // Use local date parts to avoid UTC timezone shifts
      const localDateStr = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
      showDayPopup(localDateStr);
    },
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },
    events: '?ajax=1',
    dateClick: function(info) {
      showDayPopup(info.dateStr);
    },
    eventDataTransform: function(eventData) {
      // client-side filtering using extendedProps.type
      const type = eventData.extendedProps ? eventData.extendedProps.type : null;
      if (!type || activeFilters.includes(type)) {
        eventData.display = 'auto';
      } else {
        eventData.display = 'none';
      }
      return eventData;
    },
    eventClick: function(info) {
      const props = info.event.extendedProps;
      document.getElementById('popup-type').innerText = props.type;
      document.getElementById('popup-type').style.color = info.event.backgroundColor;
      document.getElementById('popup-title').innerText = info.event.title;
      document.getElementById('popup-desc').innerText = props.desc || 'No additional details available.';
      document.getElementById('popup-link').href = props.link;
      
      document.getElementById('popupBackdrop').style.display = 'block';
      document.getElementById('eventPopup').style.display = 'block';
    }
  });
  calendar.render();
});

function showDayPopup(clickedDate) {
  const allEvents = calendar.getEvents();
  const dayEvents = allEvents.filter(e => {
      // Robust date comparison (YYYY-MM-DD)
      const d = e.start;
      const eDate = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
      return eDate === clickedDate && e.display !== 'none';
  });

  document.getElementById('day-popup-date').innerText = new Date(clickedDate).toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });
  const listContainer = document.getElementById('day-events-list');
  
  if (dayEvents.length === 0) {
      listContainer.innerHTML = '<div class="text-center p-4 text-muted"><i class="fas fa-calendar-day d-block mb-2" style="font-size:24px; opacity:0.3;"></i>No tasks scheduled for this day.</div>';
  } else {
      listContainer.innerHTML = dayEvents.map(e => `
        <a href="${e.extendedProps.link}" class="day-event-item">
            <div class="day-event-dot" style="background:${e.backgroundColor}"></div>
            <div style="flex:1;">
                <div class="fw-700" style="font-size:14px;">${e.title}</div>
                <div class="text-muted" style="font-size:11px;">${e.extendedProps.type}</div>
            </div>
            <i class="fas fa-chevron-right" style="font-size:10px; opacity:0.3;"></i>
        </a>
      `).join('');
  }

  document.getElementById('popupBackdrop').style.display = 'block';
  document.getElementById('dayPopup').style.display = 'block';
}

function toggleCalendarFilter(el) {
    const type = el.dataset.type;
    if (activeFilters.includes(type)) {
        activeFilters = activeFilters.filter(f => f !== type);
        el.classList.add('inactive');
    } else {
        activeFilters.push(type);
        el.classList.remove('inactive');
    }
    calendar.refetchEvents();
}

function closePopup() {
  document.getElementById('popupBackdrop').style.display = 'none';
  document.getElementById('eventPopup').style.display = 'none';
  document.getElementById('dayPopup').style.display = 'none';
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
