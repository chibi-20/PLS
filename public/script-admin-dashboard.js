// Admin Dashboard JavaScript Functions

// Global variables for charts
let subjectChart = null;
let gradeChart = null;
let overallGradeChart = null;

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
  initializeCharts();
  setupEventListeners();
});

function initializeCharts() {
  // Subject Chart
  const subjectCtx = document.getElementById('subjectChart').getContext('2d');
  subjectChart = new Chart(subjectCtx, {
    type: 'doughnut',
    data: {
      labels: [],
      datasets: [{
        data: [],
        backgroundColor: [
          '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', 
          '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F',
          '#BB8FCE', '#85C1E9', '#F8C471', '#82E0AA'
        ],
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverBorderWidth: 3,
        hoverBorderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 12,
            font: { size: 10 },
            usePointStyle: true,
            pointStyle: 'circle'
          }
        }
      }
    }
  });

  // Grade Level Chart
  const gradeCtx = document.getElementById('gradeChart').getContext('2d');
  gradeChart = new Chart(gradeCtx, {
    type: 'bar',
    data: {
      labels: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
      datasets: [{
        data: [0, 0, 0, 0],
        backgroundColor: [
          '#FF6B6B',  // Grade 7 - Coral Red
          '#4ECDC4',  // Grade 8 - Turquoise  
          '#45B7D1',  // Grade 9 - Sky Blue
          '#96CEB4'   // Grade 10 - Mint Green
        ],
        borderColor: [
          '#E55353',
          '#3CBAB3', 
          '#3498DB',
          '#7FB069'
        ],
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { font: { size: 10 } }
        },
        x: {
          ticks: { font: { size: 10 } }
        }
      }
    }
  });

  // Overall Grade Distribution Chart
  const overallCtx = document.getElementById('overallGradeChart').getContext('2d');
  overallGradeChart = new Chart(overallCtx, {
    type: 'pie',
    data: {
      labels: ['Excellent', 'Very Good', 'Good', 'Satisfactory', 'Fair', 'Needs Improvement', 'Poor'],
      datasets: [{
        data: [0, 0, 0, 0, 0, 0, 0],
        backgroundColor: [
          '#00D2FF',  // Excellent - Bright Cyan
          '#3F51B5',  // Very Good - Indigo
          '#4CAF50',  // Good - Green
          '#FFEB3B',  // Satisfactory - Yellow
          '#FF9800',  // Fair - Orange
          '#FF5722',  // Needs Improvement - Deep Orange
          '#F44336'   // Poor - Red
        ],
        borderColor: '#ffffff',
        borderWidth: 3,
        hoverBorderWidth: 4,
        hoverOffset: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 8,
            font: { size: 9 },
            usePointStyle: true,
            pointStyle: 'circle'
          }
        }
      }
    }
  });
}

function setupEventListeners() {
  // Filter change listeners
  document.getElementById('filterSchoolYear').addEventListener('change', loadSubjectOverview);
  document.getElementById('filterSubject').addEventListener('change', loadSubjectOverview);
  document.getElementById('filterGradeLevel').addEventListener('change', loadSubjectOverview);
  document.getElementById('filterQuarter').addEventListener('change', loadSubjectOverview);
  
  document.getElementById('analyticsSchoolYear').addEventListener('change', loadTeacherAnalytics);
  document.getElementById('analyticsSubject').addEventListener('change', loadTeacherAnalytics);
  document.getElementById('analyticsGrade').addEventListener('change', loadTeacherAnalytics);
  document.getElementById('analyticsQuarter').addEventListener('change', loadTeacherAnalytics);
}

async function loadSubjectOverview() {
  const schoolYearFilter = document.getElementById('filterSchoolYear').value;
  const subjectFilter = document.getElementById('filterSubject').value;
  const gradeFilter = document.getElementById('filterGradeLevel').value;
  const quarterFilter = document.getElementById('filterQuarter').value;
  
  try {
    // Build query parameters
    const params = new URLSearchParams();
    if (schoolYearFilter) params.append('school_year', schoolYearFilter);
    if (subjectFilter) params.append('subject', subjectFilter);
    if (gradeFilter) params.append('grade_level', gradeFilter);
    if (quarterFilter) params.append('quarter', quarterFilter);
    
    const response = await fetch(`backend/admin_get_subject_proficiency.php?${params.toString()}`);
    const result = await response.json();
    
    if (!result.success) {
      showError('Failed to load subject proficiency data: ' + result.message);
      return;
    }
    
    const subjectData = result.data || {};
    displaySubjectProficiencyOverview(subjectData);
    updateSidebarChartsFromSubjectData(subjectData);
    
  } catch (error) {
    console.error('Error loading subject overview:', error);
    showError('Error loading subject overview data');
  }
}

function displaySubjectProficiencyOverview(subjectData) {
  const summaryContainer = document.getElementById('subjectSummary');
  const teachersContainer = document.getElementById('teachersGrid');
  
  // Group data by subject
  const subjectGroups = {};
  Object.values(subjectData).forEach(data => {
    const key = data.subject;
    if (!subjectGroups[key]) {
      subjectGroups[key] = {
        subject: data.subject,
        quarters: {},
        totalStudents: 0,
        totalGrades: 0,
        avgGrade: 0
      };
    }
    
    const quarterKey = `Q${data.quarter}`;
    subjectGroups[key].quarters[quarterKey] = data;
    subjectGroups[key].totalStudents += data.total_students;
    subjectGroups[key].totalGrades += (data.avg_grade * data.total_students);
  });
  
  // Calculate overall averages
  Object.values(subjectGroups).forEach(group => {
    if (group.totalStudents > 0) {
      group.avgGrade = (group.totalGrades / group.totalStudents).toFixed(1);
    }
  });
  
  // Create summary cards for subjects
  let summaryHTML = '';
  Object.values(subjectGroups).forEach(group => {
    const quarterCount = Object.keys(group.quarters).length;
    
    summaryHTML += `
      <div class="summary-card">
        <div class="summary-title">
          <span>📚</span>
          ${group.subject}
        </div>
        <div class="summary-number">${group.avgGrade}%</div>
        <div class="summary-subtitle">
          ${group.totalStudents} students • ${quarterCount} quarters
          <br>Average Performance
        </div>
      </div>
    `;
  });
  
  summaryContainer.innerHTML = summaryHTML;
  
  // Display subject proficiency cards
  if (Object.keys(subjectGroups).length === 0) {
    teachersContainer.innerHTML = '<div class="no-data-message">No subject data found matching the current filters.</div>';
    return;
  }
  
  let cardsHTML = '';
  Object.values(subjectGroups).forEach(group => {
    cardsHTML += `
      <div class="subject-proficiency-card">
        <div class="subject-header">
          <h3>${group.subject}</h3>
          <div class="subject-stats">
            <span class="stat-badge">${group.totalStudents} Students</span>
            <span class="stat-badge">${group.avgGrade}% Avg</span>
          </div>
        </div>
        
        <div class="quarters-grid">
    `;
    
    // Display quarter data
    ['Q1', 'Q2', 'Q3', 'Q4'].forEach(quarter => {
      const quarterData = group.quarters[quarter];
      if (quarterData) {
        cardsHTML += `
          <div class="quarter-card">
            <div class="quarter-title">Quarter ${quarterData.quarter}</div>
            <div class="quarter-avg">${quarterData.avg_grade}%</div>
            <div class="quarter-stats">
              <span class="stat-badge">👦 ${quarterData.total_male_count}</span>
              <span class="stat-badge">👧 ${quarterData.total_female_count}</span>
            </div>
            <div class="proficiency-levels-detailed">
              <div class="prof-level excellent">
                <div class="prof-level-header">
                  <span class="prof-label">Excellent (98-100)</span>
                  <span class="prof-count">${quarterData.excellent_count}</span>
                </div>
                <div class="gender-breakdown">
                  <span class="gender-count">👦 ${quarterData.excellent_male_count}</span>
                  <span class="gender-count">👧 ${quarterData.excellent_female_count}</span>
                </div>
              </div>
              <div class="prof-level very-good">
                <div class="prof-level-header">
                  <span class="prof-label">Very Good (95-97)</span>
                  <span class="prof-count">${quarterData.very_good_count}</span>
                </div>
                <div class="gender-breakdown">
                  <span class="gender-count">👦 ${quarterData.very_good_male_count}</span>
                  <span class="gender-count">👧 ${quarterData.very_good_female_count}</span>
                </div>
              </div>
              <div class="prof-level good">
                <div class="prof-level-header">
                  <span class="prof-label">Good (90-94)</span>
                  <span class="prof-count">${quarterData.good_count}</span>
                </div>
                <div class="gender-breakdown">
                  <span class="gender-count">👦 ${quarterData.good_male_count}</span>
                  <span class="gender-count">👧 ${quarterData.good_female_count}</span>
                </div>
              </div>
              <div class="prof-level satisfactory">
                <div class="prof-level-header">
                  <span class="prof-label">Satisfactory (85-89)</span>
                  <span class="prof-count">${quarterData.satisfactory_count}</span>
                </div>
                <div class="gender-breakdown">
                  <span class="gender-count">👦 ${quarterData.satisfactory_male_count}</span>
                  <span class="gender-count">👧 ${quarterData.satisfactory_female_count}</span>
                </div>
              </div>
              <div class="prof-level fair">
                <div class="prof-level-header">
                  <span class="prof-label">Fair (80-84)</span>
                  <span class="prof-count">${quarterData.fair_count}</span>
                </div>
                <div class="gender-breakdown">
                  <span class="gender-count">👦 ${quarterData.fair_male_count}</span>
                  <span class="gender-count">👧 ${quarterData.fair_female_count}</span>
                </div>
              </div>
              <div class="prof-level needs-improvement">
                <div class="prof-level-header">
                  <span class="prof-label">Needs Improvement (75-79)</span>
                  <span class="prof-count">${quarterData.needs_improvement_count}</span>
                </div>
                <div class="gender-breakdown">
                  <span class="gender-count">👦 ${quarterData.needs_improvement_male_count}</span>
                  <span class="gender-count">👧 ${quarterData.needs_improvement_female_count}</span>
                </div>
              </div>
              <div class="prof-level poor">
                <div class="prof-level-header">
                  <span class="prof-label">Poor (Below 75)</span>
                  <span class="prof-count">${quarterData.poor_count}</span>
                </div>
                <div class="gender-breakdown">
                  <span class="gender-count">👦 ${quarterData.poor_male_count}</span>
                  <span class="gender-count">👧 ${quarterData.poor_female_count}</span>
                </div>
              </div>
            </div>
          </div>
        `;
      } else {
        cardsHTML += `
          <div class="quarter-card empty">
            <div class="quarter-title">Quarter ${quarter.slice(1)}</div>
            <div class="no-data">No Data</div>
          </div>
        `;
      }
    });
    
    cardsHTML += `
        </div>
      </div>
    `;
  });
  
  teachersContainer.innerHTML = cardsHTML;
}

function updateSidebarCharts(teachers) {
  // Subject distribution
  const subjectCounts = {};
  const gradeCounts = { 'Grade 7': 0, 'Grade 8': 0, 'Grade 9': 0, 'Grade 10': 0 };
  
  teachers.forEach(teacher => {
    const subject = teacher.subject_taught;
    const grade = teacher.grade_level;
    
    subjectCounts[subject] = (subjectCounts[subject] || 0) + 1;
    if (gradeCounts.hasOwnProperty(grade)) {
      gradeCounts[grade]++;
    }
  });
  
  // Update subject chart
  subjectChart.data.labels = Object.keys(subjectCounts);
  subjectChart.data.datasets[0].data = Object.values(subjectCounts);
  subjectChart.update();
  
  // Update grade chart
  gradeChart.data.datasets[0].data = Object.values(gradeCounts);
  gradeChart.update();
  
  // Update overall grade distribution (you'll need to implement this with actual grade data)
  loadOverallGradeDistribution();
}

async function loadOverallGradeDistribution() {
  try {
    const response = await fetch('backend/admin_get_grade_distribution.php');
    const result = await response.json();
    
    if (result.success && result.data) {
      const distribution = result.data;
      overallGradeChart.data.datasets[0].data = [
        distribution.excellent || 0,
        distribution.veryGood || 0,
        distribution.good || 0,
        distribution.satisfactory || 0,
        distribution.fair || 0,
        distribution.needsImprovement || 0,
        distribution.poor || 0
      ];
      overallGradeChart.update();
    }
  } catch (error) {
    console.error('Error loading grade distribution:', error);
  }
}

async function loadTeacherAnalytics() {
  const schoolYearFilter = document.getElementById('analyticsSchoolYear').value;
  const subjectFilter = document.getElementById('analyticsSubject').value;
  const gradeFilter = document.getElementById('analyticsGrade').value;
  const quarterFilter = document.getElementById('analyticsQuarter').value;
  
  try {
    const params = new URLSearchParams();
    if (schoolYearFilter) params.append('school_year', schoolYearFilter);
    if (subjectFilter) params.append('subject', subjectFilter);
    if (gradeFilter) params.append('grade_level', gradeFilter);
    if (quarterFilter) params.append('quarter', quarterFilter);
    
    const response = await fetch(`backend/admin_get_analytics.php?${params.toString()}`);
    const result = await response.json();
    
    if (!result.success) {
      showError('Failed to load analytics: ' + result.message);
      return;
    }
    
    displayTeacherAnalytics(result.data);
    
  } catch (error) {
    console.error('Error loading teacher analytics:', error);
    showError('Error loading teacher analytics');
  }
}

function displayTeacherAnalytics(data) {
  const container = document.getElementById('teacherAnalyticsData');
  
  if (!data || data.length === 0) {
    container.innerHTML = '<div class="no-data-message">No analytics data available for the selected filters.</div>';
    return;
  }
  
  // Create analytics table
  let analyticsHTML = `
    <div class="analytics-table-container">
      <table class="analytics-table">
        <thead>
          <tr>
            <th>Teacher</th>
            <th>Subject</th>
            <th>Grade Level</th>
            <th>Total Students</th>
            <th>Gender Split</th>
            <th>Avg Performance</th>
            <th>Excellent (98-100)</th>
            <th>Very Good (95-97)</th>
            <th>Good (90-94)</th>
            <th>Satisfactory (85-89)</th>
            <th>Fair (80-84)</th>
            <th>Needs Improvement (75-79)</th>
            <th>Poor (Below 75)</th>
          </tr>
        </thead>
        <tbody>
  `;
  
  data.forEach(teacher => {
    const totalStudents = teacher.total_students;
    analyticsHTML += `
      <tr>
        <td class="teacher-cell">
          <div class="teacher-name">${teacher.fullname}</div>
        </td>
        <td><span class="subject-badge">${teacher.subject_taught}</span></td>
        <td><span class="grade-badge">${teacher.grade_level}</span></td>
        <td class="center">${totalStudents}</td>
        <td class="center">
          <div class="gender-split">
            <span class="gender-count">👦 ${teacher.total_male_count}</span>
            <span class="gender-count">👧 ${teacher.total_female_count}</span>
          </div>
        </td>
        <td class="center"><strong>${teacher.avg_performance}%</strong></td>
        <td class="center proficiency-cell excellent">
          <div class="proficiency-details">
            <div class="total-count">${teacher.excellent_count}</div>
            <div class="gender-breakdown-small">
              <span class="gender-count">👦 ${teacher.excellent_male_count}</span>
              <span class="gender-count">👧 ${teacher.excellent_female_count}</span>
            </div>
          </div>
        </td>
        <td class="center proficiency-cell very-good">
          <div class="proficiency-details">
            <div class="total-count">${teacher.very_good_count}</div>
            <div class="gender-breakdown-small">
              <span class="gender-count">👦 ${teacher.very_good_male_count}</span>
              <span class="gender-count">👧 ${teacher.very_good_female_count}</span>
            </div>
          </div>
        </td>
        <td class="center proficiency-cell good">
          <div class="proficiency-details">
            <div class="total-count">${teacher.good_count}</div>
            <div class="gender-breakdown-small">
              <span class="gender-count">👦 ${teacher.good_male_count}</span>
              <span class="gender-count">👧 ${teacher.good_female_count}</span>
            </div>
          </div>
        </td>
        <td class="center proficiency-cell satisfactory">
          <div class="proficiency-details">
            <div class="total-count">${teacher.satisfactory_count}</div>
            <div class="gender-breakdown-small">
              <span class="gender-count">👦 ${teacher.satisfactory_male_count}</span>
              <span class="gender-count">👧 ${teacher.satisfactory_female_count}</span>
            </div>
          </div>
        </td>
        <td class="center proficiency-cell fair">
          <div class="proficiency-details">
            <div class="total-count">${teacher.fair_count}</div>
            <div class="gender-breakdown-small">
              <span class="gender-count">👦 ${teacher.fair_male_count}</span>
              <span class="gender-count">👧 ${teacher.fair_female_count}</span>
            </div>
          </div>
        </td>
        <td class="center proficiency-cell needs-improvement">
          <div class="proficiency-details">
            <div class="total-count">${teacher.needs_improvement_count}</div>
            <div class="gender-breakdown-small">
              <span class="gender-count">👦 ${teacher.needs_improvement_male_count}</span>
              <span class="gender-count">👧 ${teacher.needs_improvement_female_count}</span>
            </div>
          </div>
        </td>
        <td class="center proficiency-cell poor">
          <div class="proficiency-details">
            <div class="total-count">${teacher.poor_count}</div>
            <div class="gender-breakdown-small">
              <span class="gender-count">👦 ${teacher.poor_male_count}</span>
              <span class="gender-count">👧 ${teacher.poor_female_count}</span>
            </div>
          </div>
        </td>
      </tr>
    `;
  });
  
  analyticsHTML += `
        </tbody>
      </table>
    </div>
  `;
  
  container.innerHTML = analyticsHTML;
}

async function loadSystemReports() {
  try {
    const response = await fetch('backend/admin_get_system_stats.php');
    const result = await response.json();
    
    if (!result.success) {
      showError('Failed to load system stats: ' + result.message);
      return;
    }
    
    const stats = result.data;
    
    // Update summary numbers
    document.getElementById('totalTeachers').textContent = stats.total_teachers || 0;
    document.getElementById('totalSections').textContent = stats.total_sections || 0;
    document.getElementById('totalGrades').textContent = stats.total_grades || 0;
    document.getElementById('averagePerformance').textContent = 
      stats.average_performance ? stats.average_performance + '%' : '0%';
    
  } catch (error) {
    console.error('Error loading system reports:', error);
    showError('Error loading system reports');
  }
}

async function populateSubjectFilters() {
  try {
    const response = await fetch('backend/admin_get_subjects.php');
    const result = await response.json();
    
    if (result.success && result.data) {
      const subjects = result.data;
      
      const subjectFilter = document.getElementById('filterSubject');
      const analyticsSubject = document.getElementById('analyticsSubject');
      const exportSubject = document.getElementById('exportSubject');
      
      // Clear existing options (except "All")
      subjectFilter.innerHTML = '<option value="">All Subjects</option>';
      analyticsSubject.innerHTML = '<option value="">All Subjects</option>';
      exportSubject.innerHTML = '<option value="">Select Subject</option>';
      
      // Add subject options
      subjects.forEach(subject => {
        const option1 = new Option(subject, subject);
        const option2 = new Option(subject, subject);
        const option3 = new Option(subject, subject);
        subjectFilter.add(option1);
        analyticsSubject.add(option2);
        exportSubject.add(option3);
      });
    }
  } catch (error) {
    console.error('Error loading subjects:', error);
  }
}

function showError(message) {
  // You can implement a proper error display mechanism here
  console.error(message);
  alert(message);
}

// Export functionality
function showExportModal() {
  const modal = document.getElementById('exportModal');
  modal.style.display = 'flex';
}

function hideExportModal() {
  const modal = document.getElementById('exportModal');
  modal.style.display = 'none';
}

function exportData() {
  // Direct navigation to CSV export
  window.location.href = 'backend/admin_export_data.php';
  
  // Hide modal
  hideExportModal();
  
  // Show success message
  setTimeout(() => {
    alert('CSV export started! Your file should download automatically.');
  }, 500);
}

// Update export form based on export type
function updateExportForm() {
  const exportType = document.getElementById('exportType').value;
  const subjectGroup = document.getElementById('exportSubjectGroup');
  const gradeGroup = document.getElementById('exportGradeGroup');
  
  // Show/hide relevant fields
  if (exportType === 'subject' || exportType === 'subject_grade') {
    subjectGroup.style.display = 'block';
  } else {
    subjectGroup.style.display = 'none';
  }
  
  if (exportType === 'grade' || exportType === 'subject_grade') {
    gradeGroup.style.display = 'block';
  } else {
    gradeGroup.style.display = 'none';
  }
}

// Utility function to format dates
function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString();
}

// Update sidebar charts from subject data
function updateSidebarChartsFromSubjectData(subjectData) {
  // Subject distribution
  const subjectCounts = {};
  const gradeCounts = { 'Grade 7': 0, 'Grade 8': 0, 'Grade 9': 0, 'Grade 10': 0 };
  
  Object.values(subjectData).forEach(data => {
    const subject = data.subject;
    const grade = data.grade_level;
    
    subjectCounts[subject] = (subjectCounts[subject] || 0) + 1;
    if (gradeCounts.hasOwnProperty(grade)) {
      gradeCounts[grade]++;
    }
  });
  
  // Update subject chart
  subjectChart.data.labels = Object.keys(subjectCounts);
  subjectChart.data.datasets[0].data = Object.values(subjectCounts);
  subjectChart.update();
  
  // Update grade chart
  gradeChart.data.datasets[0].data = Object.values(gradeCounts);
  gradeChart.update();
  
  // Update overall grade distribution
  loadOverallGradeDistribution();
}
