<?php
// =====================================================
// ISSD Management - Student: My Courses (Soft UI)
// =====================================================
define('PAGE_TITLE', 'My Courses');
require_once dirname(__DIR__, 2) . '/backend/config.php';
require_once dirname(__DIR__, 2) . '/backend/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

requireRole(ROLE_STUDENT);
$userId = currentUserId();

$sql = "SELECT c.course_name AS title, c.course_code AS code, c.description,
               c.duration, c.monthly_fee,
               sc.status AS enrollment_status, sc.start_date AS enrolled_at,
               l.name AS lecturer_name, l.email AS lecturer_email
        FROM student_courses sc
        JOIN courses c ON c.id = sc.course_id
        JOIN students s ON s.id = sc.student_id
        LEFT JOIN course_assignments ca ON c.id = ca.course_id
        LEFT JOIN lecturers l ON ca.lecturer_id = l.id
        WHERE s.user_id = ?
        ORDER BY sc.start_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$courses = $stmt->fetchAll();

require_once dirname(__DIR__, 2) . '/includes/header.php';
require_once dirname(__DIR__, 2) . '/includes/sidebar.php';
?>

<div id="page-content" style="background: transparent; box-shadow: none;">
  <div class="dark-layout-wrapper">
    
    <div class="welcome-header">
      <h1>My Courses</h1>
      <p>Your currently enrolled academic programs and past completions.</p>
    </div>

    <div class="dark-grid-4">
      <?php if(empty($courses)): ?>
          <div style="grid-column: 1 / -1;">
              <div class="glass-card" style="padding:80px; text-align:center;">
                <div style="width:80px; height:80px; border-radius:20px; background:rgba(255,255,255,0.05); color:#94a3b8; margin:0 auto 20px; font-size:32px; display:flex; align-items:center; justify-content:center;">
                  <i class="fas fa-book-open"></i>
                </div>
                <h3 style="font-weight:700; color:inherit; margin-bottom:10px;">No active courses</h3>
                <p style="color:inherit; opacity:0.7; max-width:400px; margin:0 auto;">You haven't been enrolled in any courses yet. Please contact the administration office.</p>
              </div>
          </div>
      <?php else: ?>
          <?php foreach($courses as $c): ?>
          <div class="glass-card" style="display:flex; flex-direction:column; padding: 24px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: default;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 20px 40px rgba(0,0,0,0.4), 0 0 20px rgba(34,211,238,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 32px rgba(0,0,0,0.2)';">
              <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                  <span class="dark-badge db-blue"><?= htmlspecialchars($c['code']) ?></span>
                  <span class="dark-badge <?= $c['enrollment_status']==='ongoing'?'db-green':($c['enrollment_status']==='completed'?'db-purple':'db-red') ?>">
                      <?= strtoupper($c['enrollment_status']) ?>
                  </span>
              </div>

              <h3 style="font-size:18px; font-weight:700; margin:0 0 12px 0; line-height:1.4; color:inherit;"><?= htmlspecialchars($c['title']) ?></h3>
              <div style="font-size:13.5px; line-height:1.6; margin:0 0 24px 0; flex:1; display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; opacity:0.8;">
                  <?= htmlspecialchars($c['description'] ?? 'Course materials and syllabus details are available in the portal.') ?>
              </div>
              
              <div style="display:flex; align-items:center; gap:12px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.05); margin-bottom:20px;">
                  <?php if(!empty($c['lecturer_name'])): ?>
                      <div style="width:36px; height:36px; border-radius:10px; background:rgba(34,211,238,0.1); color:#22d3ee; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; box-shadow: inset 0 0 0 1px rgba(34,211,238,0.2);">
                          <?= strtoupper(substr($c['lecturer_name'],0,1)) ?>
                      </div>
                      <div>
                          <h4 style="margin:0 0 2px 0; font-size:13px; font-weight:600; color:inherit;"><?= htmlspecialchars($c['lecturer_name']) ?></h4>
                          <p style="margin:0; font-size:11px; opacity:0.7;"><?= htmlspecialchars($c['lecturer_email']) ?></p>
                      </div>
                  <?php else: ?>
                      <div style="width:36px; height:36px; border-radius:10px; background:rgba(255,255,255,0.05); color:#64748b; display:flex; align-items:center; justify-content:center;">
                          <i class="fas fa-user"></i>
                      </div>
                      <div>
                          <h4 style="margin:0 0 2px 0; font-size:13px; font-weight:600; color:inherit;">TBA</h4>
                          <p style="margin:0; font-size:11px; opacity:0.7;">Lecturer to be assigned</p>
                      </div>
                  <?php endif; ?>
              </div>

              <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:500; opacity:0.7;">
                  <span style="display:flex; align-items:center; gap:6px;"><i class="far fa-clock"></i> <?= htmlspecialchars($c['duration']) ?></span>
                  <span style="display:flex; align-items:center; gap:6px;"><i class="far fa-calendar-alt"></i> <?= date('M Y', strtotime($c['enrolled_at'])) ?></span>
              </div>
              
              <a href="assignments/index.php" style="display:block; width:100%; text-align:center; background:rgba(255,255,255,0.05); color:inherit; padding:12px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none; transition:0.2s; margin-top:24px; border:1px solid rgba(255,255,255,0.1);" onmouseover="this.style.background='rgba(34,211,238,0.2)'; this.style.borderColor='rgba(34,211,238,0.4)';" onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='rgba(255,255,255,0.1)';">Access Materials</a>
          </div>
          <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</div><!-- /#page-content -->

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
