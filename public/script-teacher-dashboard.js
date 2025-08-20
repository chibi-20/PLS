// Elements
const sectionListDiv = document.getElementById("sectionList");
const sectionSelect = document.getElementById("sectionSelect");
const proficiencySection = document.getElementById("proficiencySection");
const addSectionForm = document.getElementById("addSectionForm");
const quarterSelect = document.getElementById("quarterSelect");
const chartSectionSelect = document.createElement("select"); // We'll add this to sidebar

// Store sections data
let sections = [];

// Load sections from database
async function loadSections() {
  try {
    const response = await fetch('../backend/get_sections.php');
    const result = await response.json();
    
    if (result.success) {
      sections = result.sections;
      renderSections();
      updateCharts(); // Update charts when sections are loaded
    } else {
      console.error('Failed to load sections:', result.message);
      showMessage('Failed to load sections: ' + result.message, 'error');
    }
  } catch (error) {
    console.error('Error loading sections:', error);
    showMessage('Error loading sections. Please refresh the page.', 'error');
  }
}

// Render sections in the UI
function renderSections() {
  // Clear existing content
  sectionListDiv.innerHTML = "";
  sectionSelect.innerHTML = '<option value="">-- Choose a section --</option>';
  proficiencySection.innerHTML = '<option value="">All Sections</option>';
  
  // Update chart section selector
  const chartSectionSelect = document.getElementById("chartSectionSelect");
  chartSectionSelect.innerHTML = '<option value="">Overall (All Sections)</option>';

  if (sections.length === 0) {
    sectionListDiv.innerHTML = '<p style="color: #666; font-style: italic; text-align: center; padding: 20px;">No sections found. Add your first section above!</p>';
    return;
  }

  // Render section list
  sections.forEach(section => {
    const sectionDiv = document.createElement("div");
    sectionDiv.className = "section-item";
    sectionDiv.innerHTML = `
      <span class="section-name">${section.section_name}</span>
      <button class="delete-section" onclick="deleteSection(${section.id}, '${section.section_name}')">Delete</button>
    `;
    sectionListDiv.appendChild(sectionDiv);

    // Add to select dropdowns
    const option1 = document.createElement("option");
    option1.value = section.section_name;
    option1.textContent = section.section_name;
    sectionSelect.appendChild(option1);

    const option2 = document.createElement("option");
    option2.value = section.section_name;
    option2.textContent = section.section_name;
    proficiencySection.appendChild(option2);

    const option3 = document.createElement("option");
    option3.value = section.section_name;
    option3.textContent = section.section_name;
    chartSectionSelect.appendChild(option3);
  });
}

// Add new section
addSectionForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  const sectionName = document.getElementById("sectionName").value.trim();
  
  if (!sectionName) {
    showMessage('Please enter a section name', 'error');
    return;
  }

  try {
    const formData = new FormData();
    formData.append('section_name', sectionName);

    const response = await fetch('../backend/add_section.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      showMessage('Section added successfully!', 'success');
      addSectionForm.reset();
      loadSections(); // Reload sections
    } else {
      showMessage(result.message, 'error');
    }
  } catch (error) {
    console.error('Error adding section:', error);
    showMessage('Error adding section. Please try again.', 'error');
  }
});

// Delete section
async function deleteSection(sectionId, sectionName) {
  if (!confirm(`Are you sure you want to delete "${sectionName}"? This action cannot be undone.`)) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append('section_id', sectionId);

    const response = await fetch('../backend/delete_section.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      showMessage('Section deleted successfully!', 'success');
      loadSections(); // Reload sections
    } else {
      showMessage(result.message, 'error');
    }
  } catch (error) {
    console.error('Error deleting section:', error);
    showMessage('Error deleting section. Please try again.', 'error');
  }
}

// Show message to user
function showMessage(message, type = 'info') {
  // Remove existing messages
  const existingMessages = document.querySelectorAll('.temp-message');
  existingMessages.forEach(msg => msg.remove());

  // Create new message
  const messageDiv = document.createElement('div');
  messageDiv.className = 'temp-message';
  messageDiv.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    max-width: 400px;
    word-wrap: break-word;
  `;

  if (type === 'success') {
    messageDiv.style.background = '#28a745';
  } else if (type === 'error') {
    messageDiv.style.background = '#dc3545';
  } else {
    messageDiv.style.background = '#007bff';
  }

  messageDiv.textContent = message;
  document.body.appendChild(messageDiv);

  // Auto remove after 4 seconds
  setTimeout(() => {
    if (messageDiv.parentNode) {
      messageDiv.remove();
    }
  }, 4000);
}

// Handle grade submission
document.getElementById("proficiencyForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const section = sectionSelect.value;
  const quarter = document.getElementById("quarterInput").value;
  const boysGrades = document.getElementById("gradesBoys").value.trim();
  const girlsGrades = document.getElementById("gradesGirls").value.trim();

  if (!section) {
    showMessage('Please select a section', 'error');
    return;
  }

  const boys = boysGrades ? boysGrades.split("\n").map(g => parseFloat(g.trim())).filter(g => !isNaN(g) && g > 0 && g <= 100) : [];
  const girls = girlsGrades ? girlsGrades.split("\n").map(g => parseFloat(g.trim())).filter(g => !isNaN(g) && g > 0 && g <= 100) : [];

  if (boys.length === 0 && girls.length === 0) {
    showMessage('Please enter at least some valid grades (1-100)', 'error');
    return;
  }

  try {
    const formData = new FormData();
    formData.append('section_name', section);
    formData.append('quarter', quarter);
    
    // Add boys grades as array
    boys.forEach(grade => {
      formData.append('boys_grades[]', grade);
    });
    
    // Add girls grades as array
    girls.forEach(grade => {
      formData.append('girls_grades[]', grade);
    });

    const response = await fetch('../backend/input_proficiency.php', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      showMessage(`Grades saved successfully! ${result.grades_inserted} grades recorded for ${section}, Quarter ${quarter}`, 'success');
      updateCharts();
      
      // Refresh proficiency data if that tab is active
      const activeTab = document.querySelector('.tab-content.active');
      if (activeTab && activeTab.id === 'proficiencyDataTab') {
        displayProficiencyData();
      }
      
      // Clear the form
      document.getElementById("gradesBoys").value = '';
      document.getElementById("gradesGirls").value = '';
    } else {
      showMessage(result.message, 'error');
    }
  } catch (error) {
    console.error('Error saving grades:', error);
    showMessage('Error saving grades. Please try again.', 'error');
  }
});

// Chart Setup
let combinedChart = new Chart(document.getElementById("combinedChart"), chartConfig());
let boysChart = new Chart(document.getElementById("boysChart"), chartConfig());
let girlsChart = new Chart(document.getElementById("girlsChart"), chartConfig());

function chartConfig() {
  return {
    type: 'bar',
    data: {
      labels: ['Excellent', 'Very Good', 'Good', 'Satisfactory', 'Fair', 'Needs Improvement', 'Poor'],
      datasets: [{
        label: 'Count',
        data: [0, 0, 0, 0, 0, 0, 0],
        backgroundColor: [
          '#28a745', // Excellent - Green
          '#20c997', // Very Good - Teal
          '#ffc107', // Good - Yellow
          '#fd7e14', // Satisfactory - Orange
          '#dc3545', // Fair - Red
          '#6f42c1', // Needs Improvement - Purple
          '#343a40'  // Poor - Dark Gray
        ],
        borderColor: [
          '#1e7e34',
          '#17a2b8',
          '#e0a800',
          '#e8651c',
          '#c82333',
          '#5a32a3',
          '#23272b'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          display: false
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const ranges = ['98-100', '95-97', '90-94', '85-89', '80-84', '75-79', 'Below 75'];
              return `${context.label} (${ranges[context.dataIndex]}): ${context.parsed.y} students`;
            }
          }
        }
      },
      scales: {
        y: { 
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        },
        x: {
          ticks: {
            maxRotation: 45,
            minRotation: 45,
            font: {
              size: 10
            }
          }
        }
      }
    }
  };
}

function categorizeGradesForChart(grades) {
  const counts = [0, 0, 0, 0, 0, 0, 0]; // Excellent, Very Good, Good, Satisfactory, Fair, Needs Improvement, Poor
  grades.forEach(score => {
    if (score >= 98) counts[0]++;          // Excellent
    else if (score >= 95) counts[1]++;     // Very Good
    else if (score >= 90) counts[2]++;     // Good
    else if (score >= 85) counts[3]++;     // Satisfactory
    else if (score >= 80) counts[4]++;     // Fair
    else if (score >= 75) counts[5]++;     // Needs Improvement
    else counts[6]++;                      // Poor
  });
  return counts;
}

function updateCharts() {
  const quarter = quarterSelect.value;
  const selectedSection = document.getElementById('chartSectionSelect').value;
  
  if (selectedSection) {
    // Show data for specific section
    fetchGradeData(selectedSection, quarter);
  } else {
    // Show overall data (all sections)
    fetchAllGradeData(quarter);
  }
}

async function fetchGradeData(sectionName, quarter) {
  try {
    const response = await fetch(`../backend/get_data.php?section=${encodeURIComponent(sectionName)}&quarter=${quarter}`);
    const result = await response.json();
    
    if (result.success) {
      const boys = result.boys || [];
      const girls = result.girls || [];
      
      const combinedCounts = categorizeGradesForChart([...boys, ...girls]);
      const boysCounts = categorizeGradesForChart(boys);
      const girlsCounts = categorizeGradesForChart(girls);

      // Update chart titles
      const chartTitle = `${sectionName} - Quarter ${quarter}`;
      updateChartTitles(chartTitle, boys.length, girls.length);

      // Update chart data
      combinedChart.data.datasets[0].data = combinedCounts;
      boysChart.data.datasets[0].data = boysCounts;
      girlsChart.data.datasets[0].data = girlsCounts;

      combinedChart.update();
      boysChart.update();
      girlsChart.update();
    } else {
      console.error('Error fetching grade data:', result.message);
      // Clear charts if no data
      updateChartsWithEmptyData('No data available');
    }
  } catch (error) {
    console.error('Error fetching grade data:', error);
    updateChartsWithEmptyData('Error loading data');
  }
}

async function fetchAllGradeData(quarter) {
  try {
    const response = await fetch('../backend/get_data.php');
    const result = await response.json();
    
    if (result.success) {
      let boysAll = [], girlsAll = [];
      
      // Combine all data for the selected quarter
      Object.values(result.data).forEach(sectionData => {
        if (sectionData.quarter == quarter) {
          boysAll = boysAll.concat(sectionData.boys || []);
          girlsAll = girlsAll.concat(sectionData.girls || []);
        }
      });

      const combinedCounts = categorizeGradesForChart([...boysAll, ...girlsAll]);
      const boysCounts = categorizeGradesForChart(boysAll);
      const girlsCounts = categorizeGradesForChart(girlsAll);

      // Update chart titles
      const chartTitle = `Overall - Quarter ${quarter}`;
      updateChartTitles(chartTitle, boysAll.length, girlsAll.length);

      // Update chart data
      combinedChart.data.datasets[0].data = combinedCounts;
      boysChart.data.datasets[0].data = boysCounts;
      girlsChart.data.datasets[0].data = girlsCounts;

      combinedChart.update();
      boysChart.update();
      girlsChart.update();
    } else {
      console.error('Error fetching all grade data:', result.message);
      updateChartsWithEmptyData('No data available');
    }
  } catch (error) {
    console.error('Error fetching all grade data:', error);
    updateChartsWithEmptyData('Error loading data');
  }
}

function updateChartsWithEmptyData(message) {
  const emptyData = [0, 0, 0, 0, 0, 0, 0];
  
  combinedChart.data.datasets[0].data = emptyData;
  boysChart.data.datasets[0].data = emptyData;
  girlsChart.data.datasets[0].data = emptyData;
  
  updateChartTitles(message, 0, 0);
  
  combinedChart.update();
  boysChart.update();
  girlsChart.update();
}

function updateChartTitles(title, boysCount, girlsCount) {
  // Update the chart container titles to show current selection and student counts
  const chartContainers = document.querySelectorAll('.chart-container h3');
  if (chartContainers.length >= 3) {
    chartContainers[0].textContent = `Combined Performance (${boysCount + girlsCount} students) - ${title}`;
    chartContainers[1].textContent = `Boys Performance (${boysCount} students) - ${title}`;
    chartContainers[2].textContent = `Girls Performance (${girlsCount} students) - ${title}`;
  }
}

// Event listeners
quarterSelect.addEventListener("change", updateCharts);
document.addEventListener('DOMContentLoaded', function() {
  // Add event listener for chart section selector after DOM is loaded
  const chartSectionSelect = document.getElementById('chartSectionSelect');
  if (chartSectionSelect) {
    chartSectionSelect.addEventListener("change", updateCharts);
  }
});

// Proficiency Data Tab Functions
function categorizeProficiency(grades) {
  const levels = {
    'excellent': [], // 98-100
    'veryGood': [],  // 95-97
    'good': [],      // 90-94
    'satisfactory': [], // 85-89
    'fair': [],      // 80-84
    'needsImprovement': [], // 75-79
    'poor': []       // Below 75
  };

  grades.forEach(grade => {
    if (grade >= 98) levels.excellent.push(grade);
    else if (grade >= 95) levels.veryGood.push(grade);
    else if (grade >= 90) levels.good.push(grade);
    else if (grade >= 85) levels.satisfactory.push(grade);
    else if (grade >= 80) levels.fair.push(grade);
    else if (grade >= 75) levels.needsImprovement.push(grade);
    else levels.poor.push(grade);
  });

  return levels;
}

function displayProficiencyData() {
  fetchAndDisplayProficiencyData();
}

async function fetchAndDisplayProficiencyData() {
  const container = document.getElementById('proficiencyDataContainer');
  const overallContainer = document.getElementById('overallProficiencyContainer');
  const selectedSection = document.getElementById('proficiencySection').value;
  const selectedQuarter = document.getElementById('proficiencyQuarter').value;
  
  container.innerHTML = '';
  overallContainer.innerHTML = '';
  
  try {
    const response = await fetch('../backend/get_data.php');
    const result = await response.json();
    
    console.log('API Response:', result); // Debug log
    
    if (!result.success) {
      console.log('API returned error:', result.message); // Debug log
      container.innerHTML = '<div class="no-data-message">Error loading data. Please try again.</div>';
      return;
    }
    
    const allData = result.data || {};
    console.log('All Data:', allData); // Debug log
    
    // Filter data based on selections
    let filteredData = {};
    Object.keys(allData).forEach(key => {
      const data = allData[key];
      const matchesSection = !selectedSection || data.section === selectedSection;
      const matchesQuarter = !selectedQuarter || data.quarter == selectedQuarter;
      
      if (matchesSection && matchesQuarter) {
        filteredData[key] = data;
      }
    });
    
    console.log('Filtered Data:', filteredData); // Debug log
    
    if (Object.keys(filteredData).length === 0) {
      container.innerHTML = '<div class="no-data-message">No grade data found. Start by inputting grades in the "Input Grades" tab.</div>';
      return;
    }

    // Use existing code for overall proficiency display
    displayProficiencyFromData(filteredData, selectedSection, selectedQuarter, container, overallContainer);
    
  } catch (error) {
    console.error('Error fetching proficiency data:', error);
    container.innerHTML = '<div class="no-data-message">Error loading proficiency data. Please try again.</div>';
  }
}

function displayProficiencyFromData(filteredData, selectedSection, selectedQuarter, container, overallContainer) {
  
  console.log('displayProficiencyFromData called with:', filteredData); // Debug log
  
  if (Object.keys(filteredData).length === 0) {
    container.innerHTML = '<div class="no-data-message">No grade data found. Start by inputting grades in the "Input Grades" tab.</div>';
    return;
  }

  // Collect all data for overall statistics
  let overallData = {
    totalStudents: 0,
    totalBoys: 0,
    totalGirls: 0,
    allGrades: [],
    allBoys: [],
    allGirls: []
  };

  let hasData = false;

  // The filteredData is already grouped by section/quarter from get_data.php
  // No need to regroup, just use it directly
  const groupedData = filteredData;

  // Collect overall data from grouped data
  Object.values(groupedData).forEach(data => {
    if (data.boys.length > 0 || data.girls.length > 0) {
      hasData = true;
      overallData.allBoys = overallData.allBoys.concat(data.boys);
      overallData.allGirls = overallData.allGirls.concat(data.girls);
      overallData.allGrades = overallData.allGrades.concat(data.boys, data.girls);
    }
  });

  overallData.totalBoys = overallData.allBoys.length;
  overallData.totalGirls = overallData.allGirls.length;
  overallData.totalStudents = overallData.totalBoys + overallData.totalGirls;

  if (hasData && overallData.totalStudents > 0) {
    displayOverallProficiency(overallData, selectedSection, selectedQuarter);
  }

  // Display individual section data (only if not filtered by specific section)
  if (!selectedSection && hasData) {
    // Add section divider
    const divider = document.createElement('div');
    divider.className = 'section-divider';
    container.appendChild(divider);
  }

  // Get unique sections from the data
  const sections = [...new Set(Object.values(filteredData).map(record => record.section))];
  
  sections.forEach(sectionName => {
    const sectionCard = document.createElement('div');
    sectionCard.className = 'proficiency-section-card';
    
    const sectionHeader = document.createElement('div');
    sectionHeader.className = 'proficiency-section-header';
    sectionHeader.textContent = sectionName;
    sectionCard.appendChild(sectionHeader);

    const quarters = selectedQuarter ? [selectedQuarter] : ['1', '2', '3', '4'];
    let sectionHasData = false;

    quarters.forEach(quarter => {
      const key = `${sectionName}_Q${quarter}`;
      const data = groupedData[key];
      
      if (data && (data.boys.length > 0 || data.girls.length > 0)) {
        sectionHasData = true;
        
        const quarterDiv = document.createElement('div');
        quarterDiv.className = 'proficiency-quarter-data';
        
        const quarterHeader = document.createElement('div');
        quarterHeader.className = 'quarter-header';
        quarterHeader.textContent = `Quarter ${quarter}`;
        quarterDiv.appendChild(quarterHeader);

        const boys = data.boys || [];
        const girls = data.girls || [];
        const allGrades = [...boys, ...girls];

        if (allGrades.length > 0) {
          const proficiencyLevels = categorizeProficiency(allGrades);
          const levelsContainer = document.createElement('div');
          levelsContainer.className = 'proficiency-levels';

          const levelConfigs = [
            { key: 'excellent', title: 'Excellent', range: '98-100', class: 'excellent' },
            { key: 'veryGood', title: 'Very Good', range: '95-97', class: 'very-good' },
            { key: 'good', title: 'Good', range: '90-94', class: 'good' },
            { key: 'satisfactory', title: 'Satisfactory', range: '85-89', class: 'satisfactory' },
            { key: 'fair', title: 'Fair', range: '80-84', class: 'fair' },
            { key: 'needsImprovement', title: 'Needs Improvement', range: '75-79', class: 'needs-improvement' },
            { key: 'poor', title: 'Poor', range: 'Below 75', class: 'poor' }
          ];

          levelConfigs.forEach(config => {
            const levelDiv = document.createElement('div');
            levelDiv.className = `proficiency-level ${config.class}`;
            
            const grades = proficiencyLevels[config.key];
            const boysCount = grades.filter(grade => boys.includes(grade)).length;
            const girlsCount = grades.filter(grade => girls.includes(grade)).length;
            
            levelDiv.innerHTML = `
              <div class="level-title">${config.title}</div>
              <div class="level-range">${config.range}</div>
              <div class="level-count">${grades.length}</div>
              <div class="gender-breakdown">
                <span class="gender-count">👦 <span class="boys">${boysCount}</span></span>
                <span class="gender-count">👧 <span class="girls">${girlsCount}</span></span>
              </div>
            `;
            
            levelsContainer.appendChild(levelDiv);
          });

          quarterDiv.appendChild(levelsContainer);
        } else {
          quarterDiv.innerHTML += '<div class="no-data-message">No grades recorded for this quarter.</div>';
        }
        
        sectionCard.appendChild(quarterDiv);
      }
    });

    if (sectionHasData) {
      container.appendChild(sectionCard);
    }
  });

  if (!hasData) {
    overallContainer.innerHTML = '<div class="no-data-message">No grade data found. Start by inputting grades in the "Input Grades" tab.</div>';
  }
}

function displayOverallProficiency(overallData, selectedSection, selectedQuarter) {
  const container = document.getElementById('overallProficiencyContainer');
  
  const overallCard = document.createElement('div');
  overallCard.className = 'overall-proficiency-card';
  
  // Header with title and filter info
  const header = document.createElement('div');
  header.className = 'overall-proficiency-header';
  
  let filterText = '';
  if (selectedSection && selectedQuarter) {
    filterText = ` - ${selectedSection}, Quarter ${selectedQuarter}`;
  } else if (selectedSection) {
    filterText = ` - ${selectedSection}`;
  } else if (selectedQuarter) {
    filterText = ` - Quarter ${selectedQuarter}`;
  } else {
    filterText = ' - All Sections & Quarters';
  }
  
  header.innerHTML = `
    <h3 class="overall-proficiency-title">
      🎯 Overall Batch Proficiency${filterText}
    </h3>
  `;
  overallCard.appendChild(header);

  // Overall statistics
  const statsDiv = document.createElement('div');
  statsDiv.className = 'overall-stats';
  
  const avgGrade = overallData.allGrades.length > 0 ? 
    (overallData.allGrades.reduce((sum, grade) => sum + grade, 0) / overallData.allGrades.length).toFixed(1) : 0;
  
  const avgBoys = overallData.allBoys.length > 0 ? 
    (overallData.allBoys.reduce((sum, grade) => sum + grade, 0) / overallData.allBoys.length).toFixed(1) : 0;
    
  const avgGirls = overallData.allGirls.length > 0 ? 
    (overallData.allGirls.reduce((sum, grade) => sum + grade, 0) / overallData.allGirls.length).toFixed(1) : 0;

  statsDiv.innerHTML = `
    <div class="overall-stat">
      <div class="stat-label">Total Students</div>
      <div class="stat-value">${overallData.totalStudents}</div>
    </div>
    <div class="overall-stat">
      <div class="stat-label">Boys</div>
      <div class="stat-value">${overallData.totalBoys}</div>
    </div>
    <div class="overall-stat">
      <div class="stat-label">Girls</div>
      <div class="stat-value">${overallData.totalGirls}</div>
    </div>
    <div class="overall-stat">
      <div class="stat-label">Overall Average</div>
      <div class="stat-value">${avgGrade}</div>
    </div>
    <div class="overall-stat">
      <div class="stat-label">Boys Average</div>
      <div class="stat-value">${avgBoys}</div>
    </div>
    <div class="overall-stat">
      <div class="stat-label">Girls Average</div>
      <div class="stat-value">${avgGirls}</div>
    </div>
  `;
  overallCard.appendChild(statsDiv);

  // Overall proficiency levels
  const levelsDiv = document.createElement('div');
  levelsDiv.className = 'overall-proficiency-levels';
  
  const proficiencyLevels = categorizeProficiency(overallData.allGrades);
  const levelsGrid = document.createElement('div');
  levelsGrid.className = 'overall-levels-grid';

  const levelConfigs = [
    { key: 'excellent', title: 'Excellent', range: '98-100', class: 'excellent' },
    { key: 'veryGood', title: 'Very Good', range: '95-97', class: 'very-good' },
    { key: 'good', title: 'Good', range: '90-94', class: 'good' },
    { key: 'satisfactory', title: 'Satisfactory', range: '85-89', class: 'satisfactory' },
    { key: 'fair', title: 'Fair', range: '80-84', class: 'fair' },
    { key: 'needsImprovement', title: 'Needs Improvement', range: '75-79', class: 'needs-improvement' },
    { key: 'poor', title: 'Poor', range: 'Below 75', class: 'poor' }
  ];

  levelConfigs.forEach(config => {
    const levelDiv = document.createElement('div');
    levelDiv.className = `overall-level ${config.class}`;
    
    const grades = proficiencyLevels[config.key];
    const boysCount = grades.filter(grade => overallData.allBoys.includes(grade)).length;
    const girlsCount = grades.filter(grade => overallData.allGirls.includes(grade)).length;
    const percentage = overallData.totalStudents > 0 ? 
      ((grades.length / overallData.totalStudents) * 100).toFixed(1) : 0;
    
    levelDiv.innerHTML = `
      <div class="overall-level-title">${config.title}</div>
      <div class="overall-level-range">${config.range}</div>
      <div class="overall-level-count">${grades.length}</div>
      <div class="overall-percentage">${percentage}% of batch</div>
      <div class="overall-gender-breakdown">
        <span class="gender-count">👦 <span class="boys">${boysCount}</span></span>
        <span class="gender-count">👧 <span class="girls">${girlsCount}</span></span>
      </div>
    `;
    
    levelsGrid.appendChild(levelDiv);
  });

  levelsDiv.appendChild(levelsGrid);
  overallCard.appendChild(levelsDiv);
  container.appendChild(overallCard);
}

// Event listeners for proficiency data tab
document.getElementById('refreshProficiencyData').addEventListener('click', displayProficiencyData);
document.getElementById('proficiencySection').addEventListener('change', displayProficiencyData);
document.getElementById('proficiencyQuarter').addEventListener('change', displayProficiencyData);

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
  loadSections();
});
