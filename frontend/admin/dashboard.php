<?php
/**
 * ISSD Management - Admin Dashboard
 * High-Density Bento Box Redesign
 */
define('PAGE_TITLE', 'Dashboard');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_ADMIN);

require_once dirname(__DIR__, 2) . '/backend/document_controller.php';

// =====================================================
// LIVE SYSTEM DATA
// =====================================================
// 1. Total Students
$total_students = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// 2. Active Students (Ongoing enrollments)
$active_students = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM student_courses WHERE status = 'ongoing'")->fetchColumn();

// 3. Payment Alerts (Overdue student payments + Pending lecturer payments)
$overdue_count = (int)$pdo->query("SELECT COUNT(*) FROM student_payments WHERE status = 'overdue'")->fetchColumn();
$lecturer_pending_count = (int)$pdo->query("SELECT COUNT(*) FROM lecturer_payments WHERE status = 'pending'")->fetchColumn();
$pending_payments = $overdue_count + $lecturer_pending_count;

// 4. Monthly Revenue (Current Month)
$current_month = date('Y-m');
$stmtRev = $pdo->prepare("SELECT SUM(amount_paid) FROM student_payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?");
$stmtRev->execute([$current_month]);
$monthly_revenue = (float)$stmtRev->fetchColumn();

// 5. Global Recent Activity Feed (Combined)
// Fixing "Illegal mix of collations" using CONVERT USING utf8mb4
$stmtActivity = $pdo->query("
    (SELECT CONVERT('student' USING utf8mb4) as type, CONVERT(full_name USING utf8mb4) as title, CONVERT('registered' USING utf8mb4) as action, created_at, id as target_id FROM students)
    UNION ALL
    (SELECT CONVERT('payment' USING utf8mb4) as type, CONVERT(CONCAT('Rs. ', FORMAT(amount_paid, 0)) USING utf8mb4) as title, CONVERT('payment received' USING utf8mb4) as action, created_at, id as target_id FROM student_payments)
    UNION ALL
    (SELECT CONVERT('lead' USING utf8mb4) as type, CONVERT(name USING utf8mb4) as title, CONVERT('new lead added' USING utf8mb4) as action, created_at, id as target_id FROM leads)
    UNION ALL
    (SELECT CONVERT('lecturer' USING utf8mb4) as type, CONVERT(name USING utf8mb4) as title, CONVERT('joined team' USING utf8mb4) as action, created_at, id as target_id FROM lecturers)
    UNION ALL
    (SELECT 
        CONVERT(CASE 
            WHEN action LIKE 'student%' THEN 'student'
            WHEN action LIKE 'lead%' THEN 'lead'
            WHEN action LIKE 'payment%' OR action LIKE 'lecturer_payout' THEN 'payment'
            WHEN action LIKE 'lecturer%' THEN 'lecturer'
            ELSE 'system'
        END USING utf8mb4) as type,
        CONVERT(action USING utf8mb4) as title,
        CONVERT(details USING utf8mb4) as action,
        created_at,
        id as target_id
     FROM activity_log)
    ORDER BY created_at DESC
    LIMIT 10
");
$global_activities = $stmtActivity->fetchAll();
// Take first 3 for the main bento card
$recent_activities = array_slice($global_activities, 0, 3);

// 7. Dynamic Today's Agenda (Payments + Follow-ups)
$today = date('Y-m-d');

$agenda = [];

// 7a. Lead Follow-ups
$stmtLeads = $pdo->prepare("SELECT id, name, phone, next_followup_datetime as time, notes FROM leads WHERE DATE(next_followup_datetime) = ? AND status != 'converted' LIMIT 3");
$stmtLeads->execute([$today]);
while($row = $stmtLeads->fetch()) {
    $agenda[] = [
        'id'    => $row['id'],
        'type'  => 'Lead Call',
        'icon'  => 'fa-phone-volume',
        'color' => '#f43f5e',
        'time'  => date('h:i A', strtotime($row['time'])),
        'title' => "Call " . $row['name'],
        'desc'  => $row['phone'] . ($row['notes'] ? " • " . $row['notes'] : ""),
        'link'  => BASE_URL . "/admin/leads/index.php?highlight_id=" . $row['id'],
        'phone' => $row['phone'],
        'category' => 'lead'
    ];
}

// 7b. Student Follow-ups
$stmtStudents = $pdo->prepare("SELECT id, full_name, phone_number, next_follow_up as time, follow_up_note FROM students WHERE DATE(next_follow_up) = ? LIMIT 3");
$stmtStudents->execute([$today]);
while($row = $stmtStudents->fetch()) {
    $agenda[] = [
        'id'    => $row['id'],
        'type'  => 'Student Follow-up',
        'icon'  => 'fa-headset',
        'color' => '#6366f1',
        'time'  => 'Today',
        'title' => "Follow up: " . $row['full_name'],
        'desc'  => $row['phone_number'] . ($row['follow_up_note'] ? " • " . $row['follow_up_note'] : ""),
        'link'  => BASE_URL . "/admin/students/index.php?highlight_id=" . $row['id'],
        'phone' => $row['phone_number'],
        'category' => 'student'
    ];
}

// 7c. Pending/Overdue Payments
$stmtPayments = $pdo->prepare("
    SELECT sp.id, s.full_name, sp.total_due, sp.next_due_date, c.course_name 
    FROM student_payments sp 
    JOIN students s ON sp.student_id = s.id 
    JOIN courses c ON sp.course_id = c.id
    WHERE sp.status = 'overdue' OR DATE(sp.next_due_date) = ? 
    LIMIT 3
");
$stmtPayments->execute([$today]);
while($row = $stmtPayments->fetch()) {
    $agenda[] = [
        'id'    => $row['id'],
        'type'  => 'Payment Due',
        'icon'  => 'fa-hand-holding-dollar',
        'color' => '#10b981',
        'time'  => 'URGENT',
        'title' => "Collection: " . $row['full_name'],
        'desc'  => $row['course_name'] . " • Rs. " . number_format($row['total_due'], 0),
        'link'  => BASE_URL . "/admin/finance/index.php?highlight_id=" . $row['id'],
        'category' => 'payment'
    ];
}

// Sort by time (Lead calls first by time, others after)
usort($agenda, function($a, $b) {
    return strcmp($a['time'], $b['time']);
});

// Fallback if empty
if (empty($agenda)) {
    $agenda[] = [
        'id'    => 0,
        'type'  => 'Notice',
        'icon'  => 'fa-calendar-check',
        'color' => '#64748b',
        'time'  => '--:--',
        'title' => "No Urgent Tasks",
        'desc'  => "Enjoy your day! All calls and payments are up to date.",
        'link'  => '#',
        'phone' => '',
        'category' => 'notice'
    ];
}

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<style>
  :root {
    --bento-bg: rgba(255, 255, 255, 0.85);
    --bento-border: rgba(255, 255, 255, 0.5);
    --bento-radius: 28px;
    --accent-indigo: #6366f1;
    --accent-emerald: #10b981;
    --accent-amber: #f59e0b;
    --accent-rose: #f43f5e;
  }


  /* --- Dashboard Container --- */
  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: minmax(100px, auto);
    gap: 24px;
    padding: 10px 32px 32px 32px;
  }

  /* --- Bento Base Card --- */
  .bento-card {
    background: var(--bento-bg);
    backdrop-filter: blur(12px);
    border: 1px solid var(--bento-border);
    border-radius: var(--bento-radius);
    padding: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
  }
  .bento-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: var(--accent-indigo);
  }

  /* --- Grid Spans --- */
  .span-3 { grid-column: span 3; }
  .span-4 { grid-column: span 4; }
  .span-6 { grid-column: span 6; }
  .span-8 { grid-column: span 8; }
  .span-12 { grid-column: span 12; }

  @media (max-width: 1200px) {
    .span-3, .span-4, .span-6, .span-8 { grid-column: span 6; }
  }
  @media (max-width: 768px) {
    /* --- 2-column grid: stat cards side by side, rest full width --- */
    .dashboard-grid {
      display: grid !important;
      grid-template-columns: 1fr 1fr !important;
      padding: 10px 20px !important;
      gap: 12px !important;
      width: 100% !important;
      box-sizing: border-box !important;
      overflow-x: hidden !important;
    }

    /* Full-width cards: hero, quick actions, activity, docs, agenda */
    .bento-card.hero-card,
    .span-4, .span-6, .span-8, .span-12 {
      grid-column: span 2 !important;
      width: 100% !important;
      min-width: 0 !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
    }

    /* Stat cards: 2 per row */
    .bento-card.span-3 {
      grid-column: span 1 !important;
      width: 100% !important;
      min-width: 0 !important;
      max-width: 100% !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
    }

    /* Hero Card: uniform padding so inner box is centered */
    .bento-card.hero-card,
    .hero-card {
      padding: 12px !important;
      flex-direction: column !important;
      height: auto !important;
      min-height: auto !important;
      position: relative !important;
    }

    /* Show illustration but dimmed — overlay via pseudo-element */
    .hero-card::after {
      content: '' !important;
      position: absolute !important;
      inset: 0 !important;
      background: rgba(0, 0, 0, 0.45) !important;
      border-radius: inherit !important;
      z-index: 2 !important;
      pointer-events: none !important;
    }

    /* Illustration: visible, scaled up, floating animation restored */
    .hero-card .hero-illustration {
      display: block !important;
      opacity: 0.4 !important;
      visibility: visible !important;
      width: 160% !important;
      max-width: none !important;
      right: -30% !important;
      top: 50% !important;
      z-index: 1 !important;
      /* Let the keyframe handle transform (it already includes translateY(-50%)) */
      animation: floatIllustration 6s ease-in-out infinite !important;
    }

    /* Clock: anchor inside card, top-right — above overlay */
    .hero-card .hero-clock {
      position: absolute !important;
      top: 10px !important;
      right: 10px !important;
      font-size: 10px !important;
      padding: 3px 9px !important;
      background: rgba(255,255,255,0.9) !important;
      color: #000 !important;
      border-radius: 100px !important;
      font-weight: 800 !important;
      z-index: 10 !important;
    }

    /* Inner glass box: centered, full width within padding */
    .hero-content {
      width: 100% !important;
      max-width: 100% !important;
      padding: 16px 18px !important;
      margin-top: 30px !important;
      margin-bottom: 0 !important;
      flex: 1 !important;
      align-self: stretch !important;
      box-sizing: border-box !important;
      background: rgba(255, 255, 255, 0.08) !important;
      backdrop-filter: blur(6px) !important;
      border: 1.5px solid rgba(255, 255, 255, 0.35) !important;
      border-radius: 20px !important;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.15) !important;
      position: relative !important;
      z-index: 10 !important;
    }

    /* Text styles */
    .hero-tag {
      font-size: 9px !important;
      padding: 3px 8px !important;
      margin-bottom: 5px !important;
      background: rgba(255,255,255,0.2) !important;
      color: #fbbf24 !important;
      border-color: rgba(251,191,36,0.4) !important;
      text-shadow: none !important;
    }
    .hero-title {
      font-size: 18px !important;
      margin-bottom: 4px !important;
      word-break: break-word !important;
      line-height: 1.25 !important;
      color: #ffffff !important;
      text-shadow: 0 1px 4px rgba(0,0,0,0.4) !important;
    }
    .hero-sub {
      font-size: 12px !important;
      line-height: 1.5 !important;
      color: rgba(255,255,255,0.95) !important;
      text-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
      margin-bottom: 6px !important;
    }
    .hero-sub br { display: block !important; }
    .hero-sub a {
      display: block !important;
      font-size: 11px !important;
      padding: 4px 10px !important;
      margin-top: 5px !important;
      width: fit-content !important;
      background: rgba(255,255,255,0.18) !important;
      border-color: rgba(251,191,36,0.5) !important;
    }
    .mt-4 { margin-top: 8px !important; }
    .hero-content .btn { font-size: 11px !important; padding: 6px 14px !important; }

    /* Keep "to review" span inline */
    .hero-sub > span {
      display: inline !important;
      white-space: normal !important;
      font-size: 11px !important;
      color: rgba(255,255,255,0.85) !important;
    }

    /* --- Action Buttons Grid: 2 per row --- */
    .action-grid {
      display: grid !important;
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 10px !important;
      height: auto !important;
    }
    .action-btn {
      padding: 14px 10px !important;
      font-size: 12px !important;
    }
    .action-btn i { font-size: 20px !important; }
    .action-btn span.action-sub { font-size: 9px !important; }

    /* --- Stat Cards mobile: scale down to fit 2-column grid --- */
    .bento-card.span-3 {
      padding: 12px 10px !important;
    }
    .bento-card.span-3 .stat-header {
      margin-bottom: 6px !important;
    }
    .bento-card.span-3 .stat-icon {
      width: 32px !important;
      height: 32px !important;
      font-size: 14px !important;
      border-radius: 10px !important;
    }
    .bento-card.span-3 .stat-value {
      font-size: 22px !important;
      margin-bottom: 2px !important;
    }
    .bento-card.span-3 .stat-label {
      font-size: 9px !important;
      letter-spacing: 0.3px !important;
      white-space: normal !important;
      word-break: break-word !important;
    }
    .bento-card.span-3 .stat-trend {
      font-size: 9px !important;
      padding: 2px 5px !important;
    }
  }

  /* --- Stat Components --- */
  .stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
  .stat-icon {
    width: 52px; height: 52px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
  }
  .stat-value { font-size: 34px; font-weight: 800; color: #0f172a; margin-bottom: 2px; letter-spacing: -1px; }
  .stat-label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; }
  
  .stat-trend { 
    font-size: 10px; 
    font-weight: 800; 
    padding: 5px 12px; 
    border-radius: 100px; 
    display: flex;
    align-items: center;
    gap: 4px;
    backdrop-filter: blur(5px);
  }
  .trend-up { background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.1); }
  .trend-down { background: rgba(239, 68, 68, 0.12); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.1); }
  .trend-neutral { background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.1); }

  /* Icon Glows */
  .stat-icon.indigo { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; box-shadow: 0 8px 16px rgba(99, 102, 241, 0.25); }
  .stat-icon.emerald { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; box-shadow: 0 8px 16px rgba(16, 185, 129, 0.25); }
  .stat-icon.amber { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; box-shadow: 0 8px 16px rgba(245, 158, 11, 0.25); }
  .stat-icon.rose { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); color: #fff; box-shadow: 0 8px 16px rgba(244, 63, 94, 0.25); }

  /* --- Welcome Hero --- */
  .hero-card {
    background: linear-gradient(135deg, #1e4d4d 0%, #345b5b 50%, #34d399 100%);
    color: #fff;
    grid-column: span 8;
    display: flex;
    position: relative;
    overflow: hidden;
    padding: 30px !important;
    box-shadow: 0 20px 50px rgba(79, 70, 229, 0.3) !important;
  }
  .hero-card::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle at center, rgba(52, 211, 153, 0.15) 0%, transparent 40%);
    pointer-events: none;
    z-index: 2;
  }
  .hero-content {
    position: relative;
    z-index: 5;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    max-width: 550px;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 20px 30px;
    border-radius: 24px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
  }
  .hero-title { 
    font-size: 32px;
    font-weight: 800; 
    margin-bottom: 6px; 
    letter-spacing: -0.5px; 
    line-height: 1.1; 
    color: #fff;
    font-family: 'Poppins', sans-serif;
  }
  .hero-sub { 
    font-size: 16px; 
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500; 
    line-height: 1.6; 
  }
  .hero-illustration {
    position: absolute;
    right: -20px;
    top: 50%;
    transform: translateY(-50%);
    width: 450px;
    height: auto;
    z-index: 1;
    filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));
    animation: floatIllustration 6s ease-in-out infinite;
    mask-image: linear-gradient(to left, black 80%, transparent 100%);
    pointer-events: none;
  }
  @keyframes floatIllustration {
    0%, 100% { transform: translateY(-50%) translateX(0); }
    50% { transform: translateY(-55%) translateX(-10px); }
  }
  .hero-tag { 
    font-size: 11px; 
    font-weight: 800; 
    text-transform: uppercase; 
    letter-spacing: 2px; 
    color: #fbbf24;
    margin-bottom: 10px;
    background: rgba(251, 191, 36, 0.1);
    padding: 6px 14px;
    border-radius: 30px;
    display: inline-block;
    border: 1px solid rgba(251, 191, 36, 0.2);
  }
  .hero-clock {
    position: absolute;
    top: 30px;
    right: 30px;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    padding: 10px 22px;
    border-radius: 40px;
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 15px;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* --- Quick Actions --- */
  .action-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; height: 100%; }
  /* --- Quick Actions (Premium Glassmorphism) --- */
  .action-grid { 
    display: grid; 
    grid-template-columns: repeat(2, 1fr); 
    gap: 16px; 
    height: 100%; 
  }

  .action-btn {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 24px;
    padding: 20px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #1e293b;
    font-weight: 800;
    font-size: 13px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    text-align: center;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    position: relative;
    overflow: hidden;
  }

  .action-btn::before {
    content: '';
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 100%);
    z-index: 1;
    opacity: 0;
    transition: 0.3s;
  }

  .action-btn:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
    background: rgba(255, 255, 255, 0.95);
    border-color: rgba(255, 255, 255, 1);
  }

  .action-btn:hover::before { opacity: 1; }

  .action-btn i {
    font-size: 26px;
    margin-bottom: 4px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
  }

  .action-btn:hover i {
    transform: scale(1.2) rotate(5deg);
  }

  .action-btn span.action-label {
    position: relative;
    z-index: 2;
    display: block;
  }

  .action-btn span.action-sub {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    opacity: 0.8;
    margin-top: -2px;
    z-index: 2;
  }

  /* Specific Button Themes */
  .btn-student i { color: #6366f1; }
  .btn-payment i { color: #10b981; }
  .btn-course i  { color: #f59e0b; }
  .btn-notice i  { color: #f43f5e; }

  .btn-student:hover { border-bottom: 3px solid #6366f1; }
  .btn-payment:hover { border-bottom: 3px solid #10b981; }
  .btn-course:hover  { border-bottom: 3px solid #f59e0b; }
  .btn-notice:hover  { border-bottom: 3px solid #f43f5e; }

  /* --- Schedule List (Redesigned for Cohesion) --- */
  .bento-schedule { display: flex; flex-direction: column; gap: 12px; }
  .schedule-row {
    display: flex; 
    align-items: center;
    gap: 8px; 
    padding: 16px 20px 16px 14px; 
    border-radius: 20px;
    background: rgba(255,255,255,0.4); 
    border: 1px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  .s-agenda-item:hover {
    background: rgba(255,255,255,0.05); 
    border-color: var(--accent-indigo);
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  }

  .s-time-col { 
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 55px;
    gap: 4px;
  }
  .s-time { 
    font-size: 11px; 
    font-weight: 800; 
    color: #475569;
    text-transform: uppercase;
  }
  .s-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
  }
  .s-info { flex: 1; }
  .s-info h4 { font-size: 15px; font-weight: 800; margin: 0; color: #0f172a; line-height: 1.3; }
  .s-info p { font-size: 12px; margin: 4px 0 0; color: #64748b; font-weight: 500; }
  .s-category-tag {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 4px;
    display: block;
  }

  /* --- Table Custom --- */
  .modern-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
  .modern-table tr { background: rgba(255,255,255,0.3); transition: 0.3s; }
  .modern-table tr:hover { background: rgba(255,255,255,0.8); }
  .modern-table th { text-align: left; padding: 10px 16px; font-size: 11px; color: #64748b; font-weight: 800; }
  .modern-table td { padding: 14px 16px; font-size: 13px; vertical-align: middle; }
  .modern-table td:first-child, .modern-table th:first-child { border-radius: 14px 0 0 14px; }
  .modern-table td:last-child, .modern-table th:last-child { border-radius: 0 14px 14px 0; }

  .card-header-bento {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
  }
  .card-header-bento h3 { font-size: 18px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 10px; }

  /* --- DARK MODE OVERRIDES --- */
  body.lms-dark-mode {
    --bento-bg: rgba(30, 41, 59, 0.4);
    --bento-border: rgba(255, 255, 255, 0.05);
  }
  body.lms-dark-mode .stat-icon { 
    background: rgba(255, 255, 255, 0.05) !important; 
  }
  
  body.lms-dark-mode .stat-label { color: #94a3b8 !important; }
  body.lms-dark-mode .stat-value { color: #fff !important; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
  body.lms-dark-mode .stat-trend { border: 1px solid rgba(255,255,255,0.05); }



  
  body.lms-dark-mode .action-btn {
    background: rgba(30, 41, 59, 0.4) !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    color: #fff !important;
    backdrop-filter: blur(10px) !important;
  }
  
  body.lms-dark-mode .btn-student i { color: #818cf8 !important; }
  body.lms-dark-mode .btn-payment i { color: #34d399 !important; }
  body.lms-dark-mode .btn-course i  { color: #fbbf24 !important; }
  body.lms-dark-mode .btn-notice i  { color: #f87171 !important; }

  body.lms-dark-mode .action-btn:hover {
    background: rgba(30, 41, 59, 0.8) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-6px) scale(1.04) !important;
  }
  
  body.lms-dark-mode .action-btn span.action-sub { color: #94a3b8; }
  body.lms-dark-mode .schedule-row { background: rgba(15, 23, 42, 0.4); }
  body.lms-dark-mode .schedule-row:hover { background: rgba(30, 41, 59, 0.6); }
  body.lms-dark-mode .s-info h4 { color: #fff; }
  body.lms-dark-mode .s-info p { color: #94a3b8; }
  body.lms-dark-mode .s-time { color: #94a3b8; }
  body.lms-dark-mode .modern-table tr { background: rgba(15, 23, 42, 0.4); }
  body.lms-dark-mode .modern-table tr:hover { background: rgba(30, 41, 59, 0.6); }
  body.lms-dark-mode .modern-table td { color: #e2e8f0; }
  body.lms-dark-mode .hero-clock { background: rgba(15, 23, 42, 0.8); color: #fff; border-color: rgba(255,255,255,0.1); }
  
  /* Fixing the muddy grey background for the quick actions container */
  body.lms-dark-mode .bento-card.quick-actions-container {
    background: rgba(15, 23, 42, 0.3) !important;
    border: 1px solid rgba(255, 255, 255, 0.03) !important;
  }

  /* Dropdown Dark Mode Overrides */
  body.lms-dark-mode .dropdown-menu {
    background: rgba(15, 23, 42, 0.95) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    backdrop-filter: blur(20px) !important;
  }
  body.lms-dark-mode .dropdown-item {
    color: #e2e8f0 !important;
  }
  body.lms-dark-mode .dropdown-item:hover {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #fff !important;
  }
  body.lms-dark-mode .dropdown-divider {
    border-color: rgba(255, 255, 255, 0.1) !important;
  }
  body.lms-dark-mode .dropdown-item .stat-icon {
    background: rgba(255, 255, 255, 0.05) !important;
    color: #fff !important;
  }
  body.lms-dark-mode .dropdown-item .text-muted {
    color: #94a3b8 !important;
  }
</style>


<div id="page-content">
<div class="dashboard-grid">

  <?php 
    $hour = date('H');
    $greeting = "Good Evening";
    if ($hour < 12) $greeting = "Good Morning";
    elseif ($hour < 17) $greeting = "Good Afternoon";
  ?>
  <div class="bento-card hero-card">
    <div class="hero-clock" id="live-clock"><?= date('H:i:s') ?></div>
    <img src="<?= BASE_URL ?>/assets/images/dashboard/welcome.png" class="hero-illustration" alt="Welcome">
    
    <div class="hero-content">
      <div class="hero-tag">System Administrator</div>
      <div class="hero-title"><?= $greeting ?>, Admin</div>
      <div class="hero-sub">
        Institute operations are running smoothly today.
        <div class="mt-2">
          <a href="<?= BASE_URL ?>/admin/finance/index.php" class="text-decoration-none d-inline-flex align-items-center" style="background: rgba(255,255,255,0.1); padding: 8px 16px; border-radius: 12px; color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); backdrop-filter: blur(10px); transition: all 0.3s;">
            <i class="fas fa-bell me-2" style="animation: swing 2s infinite;"></i> 
            <span style="font-weight: 700;">You have <?= $pending_payments ?> payment alerts</span>
            <i class="fas fa-chevron-right ms-2" style="font-size: 10px; opacity: 0.6;"></i>
          </a>
        </div>
      </div>
      
      <div class="mt-3 d-flex gap-3">
        <a href="reports.php" class="btn-primary-grad rounded-pill px-4 fw-800 shadow-lg transition-all hover-scale d-flex align-items-center" style="font-size: 13px; position: relative; z-index: 10; height: 46px;">
          <i class="fas fa-chart-pie me-2"></i> View Analytics
        </a>
      </div>
    </div>
  </div>

  <script>
    function updateClock() {
        const now = new Date();
        const clock = document.getElementById('live-clock');
        if (clock) {
            clock.innerText = now.toLocaleTimeString('en-US', { hour12: false });
        }
    }
    setInterval(updateClock, 1000);
  </script>

  <!-- QUICK ACTIONS -->
  <div class="bento-card span-4 quick-actions-container" style="overflow: visible; z-index: 100;">
    <div class="action-grid">
      <a href="<?= BASE_URL ?>/admin/students/add.php" class="action-btn btn-student">
        <i class="fas fa-user-plus"></i>
        <span class="action-label">Add Student</span>
        <span class="action-sub">New Enrollment</span>
      </a>
      <div class="dropdown" style="height:100%;">
        <button class="action-btn w-100 h-100 dropdown-toggle border-0 btn-payment" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-receipt"></i>
            <span class="action-label">Payment</span>
            <span class="action-sub">Fees & Payouts</span>
        </button>
        <ul class="dropdown-menu p-2 animate__animated animate__fadeIn" style="border-radius:24px; width:240px; z-index: 9999; transform:translateY(15px) !important; box-shadow: 0 20px 50px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.8); backdrop-filter: blur(20px); background: rgba(255,255,255,0.9);">
            <li>
                <a class="dropdown-item p-3 rounded-4 d-flex align-items-center gap-3" href="<?= BASE_URL ?>/admin/payments/add.php">
                    <div class="stat-icon" style="width:36px; height:36px; background:#e0e7ff; color:var(--accent-indigo); font-size:14px; border-radius: 12px;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <div class="fw-800" style="font-size:13px;">Student Payment</div>
                        <div class="text-muted" style="font-size:10px;">Receive collections</div>
                    </div>
                </a>
            </li>
            <li><hr class="dropdown-divider opacity-10 my-2"></li>
            <li>
                <a class="dropdown-item p-3 rounded-4 d-flex align-items-center gap-3" href="<?= BASE_URL ?>/admin/lecturer_payments/index.php">
                    <div class="stat-icon" style="width:36px; height:36px; background:#dcfce7; color:var(--accent-emerald); font-size:14px; border-radius: 12px;">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <div class="fw-800" style="font-size:13px;">Lecturer Pay</div>
                        <div class="text-muted" style="font-size:10px;">Process payouts</div>
                    </div>
                </a>
            </li>
        </ul>
      </div>
      <a href="<?= BASE_URL ?>/admin/courses/add.php" class="action-btn btn-course">
        <i class="fas fa-book"></i>
        <span class="action-label">New Course</span>
        <span class="action-sub">Expand Catalog</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/notices.php" class="action-btn btn-notice">
        <i class="fas fa-bullhorn"></i>
        <span class="action-label">Post Notice</span>
        <span class="action-sub">Announcements</span>
      </a>
    </div>
  </div>

  <!-- STATS -->
  <div class="bento-card span-3">
    <div class="stat-header">
      <div class="stat-icon indigo"><i class="fas fa-users"></i></div>
      <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> 12.5%</div>
    </div>
    <div class="stat-value"><?= number_format($total_students) ?></div>
    <div class="stat-label">Total Students</div>
  </div>

  <div class="bento-card span-3">
    <div class="stat-header">
      <div class="stat-icon emerald"><i class="fas fa-user-check"></i></div>
      <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> 4.2%</div>
    </div>
    <div class="stat-value"><?= number_format($active_students) ?></div>
    <div class="stat-label">Active Learners</div>
  </div>

  <a href="<?= BASE_URL ?>/admin/finance/index.php" class="bento-card span-3 text-decoration-none">
    <div class="stat-header">
      <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
      <div class="stat-trend trend-neutral"><i class="fas fa-minus"></i> Low</div>
    </div>
    <div class="stat-value"><?= $pending_payments ?></div>
    <div class="stat-label">Pending Payments</div>
  </a>

  <a href="<?= BASE_URL ?>/admin/finance/index.php" class="bento-card span-3 text-decoration-none">
    <div class="stat-header">
      <div class="stat-icon rose"><i class="fas fa-wallet"></i></div>
      <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> 18%</div>
    </div>
    <div class="stat-value">Rs. <?= number_format($monthly_revenue/1000, 0) ?>k</div>
    <div class="stat-label">Monthly Revenue</div>
  </a>



<?php
// 6. Missing Documents Tracker (Real-time scan) - RESTORED
$stmtCheck = $pdo->query("SELECT id, full_name FROM students ORDER BY created_at DESC LIMIT 50");
$checkStudents = $stmtCheck->fetchAll();
$sIds = array_map('intval', array_column($checkStudents, 'id'));
$docProgress = getBulkDocCounts($pdo, $sIds);
$docStatuses = getBulkDocStatus($pdo, $sIds);
$missingStudents = [];
foreach ($checkStudents as $cs) {
    $sid = (int)$cs['id'];
    if (($docStatuses[$sid] ?? 'missing') === 'missing') {
        $prog = $docProgress[$sid] ?? ['collected' => 0, 'total' => 0];
        $reqDone  = $prog['collected'];
        $reqTotal = $prog['total'];
        
        $docRow = getOrCreateDocRecord($pdo, $sid);
        $defs = getDocumentDefinitions();
        $firstKey = '';
        foreach ($defs as $k => $d) { 
            if ($d['required'] && empty($docRow[$k.'_status'])) {
                $firstKey = $k;
                break;
            }
        }
        $missingStudents[] = [
            'id'    => $sid,
            'name'  => $cs['full_name'],
            'collected' => $reqDone,
            'total'     => $reqTotal,
            'first' => $firstKey
        ];
        if (count($missingStudents) >= 4) break;
    }
}
?>

  <!-- RECENT ACTIVITY -->
  <div class="bento-card span-8">
    <div class="card-header-bento">
      <h3><i class="fas fa-clock-rotate-left text-primary"></i> Recent Activity</h3>
      <button class="btn btn-sm btn-light rounded-pill px-3 fw-700" data-bs-toggle="modal" data-bs-target="#activityModal">View All</button>
    </div>
    <div class="table-responsive">
      <table class="modern-table">
        <thead>
          <tr class="text-muted small fw-800">
            <th>TYPE</th>
            <th>ACTIVITY</th>
            <th>DATE</th>
            <th>ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($recent_activities as $a): 
            $icon = 'fa-circle-info';
            $color = '#64748b';
            $link = '#';
            if($a['type']==='student') { $icon = 'fa-user-graduate'; $color = '#6366f1'; $link = BASE_URL.'/admin/students/index.php?highlight_id='.$a['target_id']; }
            if($a['type']==='payment') { $icon = 'fa-receipt'; $color = '#10b981'; $link = BASE_URL.'/admin/payments/index.php?highlight_id='.$a['target_id']; }
            if($a['type']==='lead') { $icon = 'fa-bullseye'; $color = '#f43f5e'; $link = BASE_URL.'/admin/leads/index.php?highlight_id='.$a['target_id']; }
            if($a['type']==='lecturer') { $icon = 'fa-chalkboard-user'; $color = '#f59e0b'; $link = BASE_URL.'/admin/lecturers/index.php?highlight_id='.$a['target_id']; }
          ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="stat-icon" style="width:30px; height:30px; font-size:12px; background: <?= $color ?>20; color: <?= $color ?>;">
                  <i class="fas <?= $icon ?>"></i>
                </div>
                <span class="fw-800 text-uppercase small" style="color: <?= $color ?>;"><?= $a['type'] ?></span>
              </div>
            </td>
            <td>
              <div class="fw-700"><?= htmlspecialchars($a['title']) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($a['action']) ?></div>
            </td>
            <td class="text-muted small"><?= date('d M, h:i A', strtotime($a['created_at'])) ?></td>
            <td>
              <a href="<?= $link ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2" style="font-size:10px;">Open</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Activity Modal -->
  <div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
      <div class="modal-content border-0" style="border-radius: 20px; overflow: hidden; background: rgba(255,255,255,0.98); backdrop-filter: blur(15px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div class="modal-header border-0 p-3 pb-0">
          <h6 class="modal-title fw-800 d-flex align-items-center gap-2" style="font-size: 16px;">
            <i class="fas fa-bolt text-warning"></i> Global Activity Feed
          </h6>
          <button type="button" class="btn-close" style="font-size: 10px;" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3">
          <div class="d-flex flex-column gap-2">
            <?php foreach($global_activities as $a): 
               $icon = 'fa-circle-info';
               $color = '#64748b';
               $link = '#';
               if($a['type']==='student') { $icon = 'fa-user-graduate'; $color = '#6366f1'; $link = BASE_URL.'/admin/students/index.php?highlight_id='.$a['target_id']; }
               if($a['type']==='payment') { $icon = 'fa-receipt'; $color = '#10b981'; $link = BASE_URL.'/admin/payments/index.php?highlight_id='.$a['target_id']; }
               if($a['type']==='lead') { $icon = 'fa-bullseye'; $color = '#f43f5e'; $link = BASE_URL.'/admin/leads/index.php?highlight_id='.$a['target_id']; }
               if($a['type']==='lecturer') { $icon = 'fa-chalkboard-user'; $color = '#f59e0b'; $link = BASE_URL.'/admin/lecturers/index.php?highlight_id='.$a['target_id']; }
            ?>
            <a href="<?= $link ?>" class="activity-item text-decoration-none p-2 rounded-3 d-flex align-items-center justify-content-between transition-all" style="border: 1px solid rgba(255,255,255,0.05);">
              <div class="d-flex align-items-center gap-2">
                <div class="stat-icon" style="width:32px; height:32px; font-size:11px; background: <?= $color ?>15; color: <?= $color ?>;">
                  <i class="fas <?= $icon ?>"></i>
                </div>
                <div>
                  <div class="fw-700 text-dark" style="font-size: 13px; line-height: 1.2;"><?= htmlspecialchars($a['title']) ?></div>
                  <div class="text-muted" style="font-size: 10.5px;"><?= htmlspecialchars($a['action']) ?></div>
                </div>
              </div>
              <div class="text-end" style="min-width: 70px;">
                <div class="text-dark fw-600" style="font-size: 10px;"><?= date('h:i A', strtotime($a['created_at'])) ?></div>
                <div class="text-muted" style="font-size: 9px;"><?= date('d M', strtotime($a['created_at'])) ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer border-0 p-3 pt-0">
          <button type="button" class="btn btn-light w-100 rounded-pill fw-700 py-2" style="font-size: 12px;" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    .activity-item:hover {
      background: rgba(255,255,255,0.05) !important;
      transform: scale(1.02);
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border-color: var(--accent-indigo) !important;
    }
  </style>

  <!-- TODAY'S AGENDA -->
  <div class="bento-card span-4">
    <div class="card-header-bento">
      <h3><i class="fas fa-list-check text-success"></i> Today's Agenda</h3>
      <div class="badge bg-success-subtle text-success border-0 rounded-pill px-3" style="font-size:11px;">
        <?= count($agenda) ?> Tasks
      </div>
    </div>
    <div class="bento-schedule">
      <?php foreach($agenda as $item): ?>
      <?php 
        $isNotice = ($item['category'] === 'notice');
        $tag = $isNotice ? 'div' : 'a';
        $attr = $isNotice ? '' : 'href="'.$item['link'].'"';
      ?>
      <<?= $tag ?> <?= $attr ?> class="schedule-row text-decoration-none" <?= $isNotice ? 'style="cursor: default;"' : '' ?>>
        <div class="s-time-col">
            <div class="s-time"><?= $item['time'] ?></div>
            <div class="s-icon-box" style="background: <?= $item['color'] ?>15; color: <?= $item['color'] ?>;">
                <i class="fas <?= $item['icon'] ?>"></i>
            </div>
        </div>
        <div class="s-info pe-2">
            <div class="d-flex justify-content-between align-items-start">
                <span class="s-category-tag" style="color: <?= $item['color'] ?>;"><?= $item['type'] ?></span>
                <?php if(!$isNotice): ?>
                    <i class="fas fa-chevron-right text-muted" style="font-size:10px; opacity: 0.5;"></i>
                <?php endif; ?>
            </div>
            <h4><?= htmlspecialchars($item['title']) ?></h4>
            <p><?= htmlspecialchars($item['desc']) ?></p>
            
            <div class="d-flex align-items-center gap-2 mt-3" style="margin-left: -4px;">
                <?php if(!empty($item['phone'])): ?>
                    <div onclick="showCallModal('<?= htmlspecialchars($item['title']) ?>', '<?= $item['phone'] ?>'); event.preventDefault(); event.stopPropagation();" 
                         class="badge bg-primary rounded-pill text-decoration-none px-3 py-2 action-badge" 
                         style="font-size:10px; cursor: pointer; white-space: nowrap;">
                        <i class="fas fa-phone me-1"></i> Call Now
                    </div>
                <?php endif; ?>
                
                <?php if(isset($item['category']) && in_array($item['category'], ['lead', 'student'])): ?>
                    <div class="badge bg-light text-dark rounded-pill border-0 px-3 py-2 action-badge snooze-btn" 
                            data-id="<?= $item['id'] ?>" 
                            data-category="<?= $item['category'] ?>"
                            data-title="<?= htmlspecialchars($item['title']) ?>"
                            style="font-size:10px; cursor: pointer; white-space: nowrap;">
                        <i class="fas fa-clock me-1 text-muted"></i> Snooze
                    </div>
                <?php endif; ?>

                <?php if($item['category'] === 'payment'): ?>
                    <div onclick="window.location.href='<?= $item['link'] ?>'; event.preventDefault(); event.stopPropagation();" class="badge bg-success rounded-pill text-decoration-none px-3 py-2 action-badge" style="font-size:10px; cursor: pointer; white-space: nowrap;">
                        <i class="fas fa-receipt me-1"></i> Process
                    </div>
                <?php endif; ?>
            </div>
        </div>
      </<?= $tag ?>>
      <?php endforeach; ?>
    </div>
    <a href="<?= BASE_URL ?>/admin/calendar.php" class="btn btn-primary-grad w-100 mt-4 rounded-pill fw-800 py-3 shadow-lg transition-all hover-scale" style="background: var(--grad-blue) !important; border: none !important;">
        <i class="fas fa-calendar-alt me-2"></i> Open Full Calendar
    </a>
  </div>

  <!-- CUSTOM MODAL: SNOOZE OPTIONS -->
  <div class="modal fade" id="snoozeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
        <div class="modal-body p-4 text-center">
            <div class="stat-icon mx-auto mb-3" style="width:50px; height:50px;">
                <i class="fas fa-clock"></i>
            </div>
            <h5 class="fw-800 mb-1" id="snoozeTargetName">Snooze Task</h5>
            <p class="text-muted small mb-4">When should we remind you again?</p>
            
            <div class="d-flex flex-column gap-2">
                <button class="btn btn-light rounded-4 py-3 fw-700 text-start d-flex justify-content-between align-items-center snooze-opt" data-time="+2 hours">
                    <span><i class="fas fa-hourglass-start me-2 text-primary"></i> In 2 Hours</span>
                    <i class="fas fa-chevron-right small opacity-50"></i>
                </button>
                <button class="btn btn-light rounded-4 py-3 fw-700 text-start d-flex justify-content-between align-items-center snooze-opt" data-time="tomorrow 09:00:00">
                    <span><i class="fas fa-sun me-2 text-warning"></i> Tomorrow Morning</span>
                    <i class="fas fa-chevron-right small opacity-50"></i>
                </button>
                <button class="btn btn-light rounded-4 py-3 fw-700 text-start d-flex justify-content-between align-items-center snooze-opt" data-time="+2 days">
                    <span><i class="fas fa-calendar-plus me-2 text-info"></i> In 2 Days</span>
                    <i class="fas fa-chevron-right small opacity-50"></i>
                </button>
            </div>
            <button type="button" class="btn btn-link text-muted fw-700 text-decoration-none mt-3 small" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <!-- CUSTOM MODAL: CALL ACTION -->
  <div class="modal fade" id="callModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
        <div class="modal-body p-4 text-center">
            <div class="stat-icon mx-auto mb-3" style="width:50px; height:50px;">
                <i class="fas fa-phone"></i>
            </div>
            <h5 class="fw-800 mb-1" id="callTargetName">Contact Lead</h5>
            <p class="fw-700 text-primary mb-4" id="callTargetPhone" style="font-size: 18px;">+94 000 000 000</p>
            
            <div class="d-flex flex-column gap-2">
                <a href="#" id="callNowLink" class="btn btn-primary rounded-pill py-3 fw-800 shadow-sm">
                    <i class="fas fa-phone-alt me-2"></i> Call Now
                </a>
                <button onclick="copyToClipboard();" class="btn btn-light rounded-pill py-3 fw-700">
                    <i class="fas fa-copy me-2"></i> Copy Number
                </button>
            </div>
            <button type="button" class="btn btn-link text-muted fw-700 text-decoration-none mt-3 small" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <style>
    .action-badge {
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: inline-flex;
        align-items: center;
    }
    .action-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        filter: brightness(1.05);
    }
    .snooze-opt { border: 1px solid transparent !important; transition: all 0.2s; }
    .snooze-opt:hover { border-color: var(--accent-indigo) !important; transform: scale(1.02); }
  </style>

  <script>
    let activeSnoozeId = null;
    let activeSnoozeCategory = null;
    let activeSnoozeBtn = null;

    function showCallModal(name, phone) {
        document.getElementById('callTargetName').innerText = name;
        document.getElementById('callTargetPhone').innerText = phone;
        document.getElementById('callNowLink').href = 'tel:' + phone;
        new bootstrap.Modal(document.getElementById('callModal')).show();
    }

    function copyToClipboard() {
        const phone = document.getElementById('callTargetPhone').innerText;
        navigator.clipboard.writeText(phone).then(() => {
            const btn = document.querySelector('#callModal .btn-light');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2"></i> Copied!';
            setTimeout(() => btn.innerHTML = originalText, 2000);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Snooze Modal Trigger
        document.querySelectorAll('.snooze-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                activeSnoozeId = this.dataset.id;
                activeSnoozeCategory = this.dataset.category;
                activeSnoozeBtn = this;
                
                document.getElementById('snoozeTargetName').innerText = this.dataset.title;
                new bootstrap.Modal(document.getElementById('snoozeModal')).show();
            });
        });

        // Snooze Option Selection
        document.querySelectorAll('.snooze-opt').forEach(opt => {
            opt.addEventListener('click', function() {
                const time = this.dataset.time;
                const modal = bootstrap.Modal.getInstance(document.getElementById('snoozeModal'));
                
                activeSnoozeBtn.disabled = true;
                activeSnoozeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                modal.hide();

                fetch('<?= BASE_URL ?>/api/agenda_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=snooze&id=${activeSnoozeId}&category=${activeSnoozeCategory}&time=${time}&csrf_token=${CSRF_TOKEN}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        activeSnoozeBtn.closest('.schedule-row').style.opacity = '0.4';
                        activeSnoozeBtn.closest('.schedule-row').style.pointerEvents = 'none';
                        activeSnoozeBtn.innerHTML = '<i class="fas fa-check"></i>';
                    } else {
                        alert(data.error || 'Snooze failed');
                        activeSnoozeBtn.disabled = false;
                        activeSnoozeBtn.innerHTML = '<i class="fas fa-clock"></i> Snooze';
                    }
                });
            });
        });
    });
  </script>

  <!-- SECTION: MISSING DOCUMENTS TRACKER -->
  <div class="bento-card span-4">
    <div class="card-header-bento">
      <h3><i class="fas fa-file-circle-exclamation text-danger"></i> Missing Documents</h3>
      <a href="<?= BASE_URL ?>/admin/documents/index.php?doc_status=missing" class="btn btn-sm btn-light rounded-pill px-3 fw-700">View All</a>
    </div>
    <div class="d-flex flex-column gap-3">
       <?php if (empty($missingStudents)): ?>
         <div class="text-center py-4 text-muted small">No students with missing documents.</div>
       <?php else: ?>
         <?php foreach($missingStudents as $d): ?>
         <a href="<?= BASE_URL ?>/admin/documents/manage.php?student_id=<?= $d['id'] ?>&highlight=<?= $d['first'] ?>" class="p-3 rounded-4 bg-danger-subtle border-0 text-decoration-none transition-all hover-scale d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-800 text-main small mb-1"><?= htmlspecialchars($d['name']) ?></div>
              <div class="text-danger fw-700" style="font-size:11px;"><i class="fas fa-file-circle-xmark me-1"></i> No documents yet</div>
            </div>
            <div class="badge bg-danger rounded-pill px-3 py-2 fw-800" style="font-size:10px;">
              <?= $d['collected'] ?> / <?= $d['total'] ?>
            </div>
         </a>
         <?php endforeach; ?>
       <?php endif; ?>
    </div>
  </div>

</div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
