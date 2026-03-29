<?php
add("http://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]."");
?>
<html>
<head>
<title>Statistiken</title>
<meta charset="utf-8">
<script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.js'></script>
</head>
<body>
<?php askQuestion() ?>
<div style="background-color: #EFF5FB; color: #2E2E2E; margin: 20px; padding: 25px; border-color: #CEE3F6; border-width: 3px; border-style: solid; border-radius: 5px; text-align: center;">
<?php echo getAll() ?><br>
<?php echo getUsers() ?>
</div>
<div style="background-color: #EFF5FB; color: #2E2E2E; margin: 20px; padding: 25px; border-color: #CEE3F6; border-width: 3px; border-style: solid; border-radius: 5px;">
  <table align="center">
    <tr>
      <td style="width: 50%"><div class="piechart"><canvas id="platformChart" width="400" height="400"></canvas></div></td>
      <td style="width: 50%"><div class="piechart"><canvas id="browserChart" width="400" height="400"></canvas></div></td>
    <tr>
  </table>
</div>
<div style="background-color: #EFF5FB; color: #2E2E2E; margin: 20px; padding: 25px; border-color: #CEE3F6; border-width: 3px; border-style: solid; border-radius: 5px;">
  <div class="linechart"><canvas id="daily_clicks"></canvas></div>
</div>
<div style="background-color: #EFF5FB; color: #2E2E2E; margin: 20px; padding: 25px; border-color: #CEE3F6; border-width: 3px; border-style: solid; border-radius: 5px;">
  <div class="linechart"><canvas id="daily_new_visitors"></canvas></div>
</div>
<script>
  <?php $stats = getStatistic("browsername"); ?>
  var ctx1 = document.getElementById("browserChart");
  var myChart1 = new Chart(ctx1, {
  type: 'doughnut',
  data: {
  labels: <?php echo json_encode($stats["labels"]); ?>,
  datasets: [{
  data: <?php echo json_encode($stats["data"]); ?>,
  backgroundColor: [
  'rgba(255, 99, 132, 0.2)',
  'rgba(54, 162, 235, 0.2)',
  'rgba(255, 206, 86, 0.2)',
  'rgba(75, 192, 192, 0.2)',
  ],
  borderColor: [
  'rgba(255,99,132,1)',
  'rgba(54, 162, 235, 1)',
  'rgba(255, 206, 86, 1)',
  'rgba(75, 192, 192, 1)',
  ],
  borderWidth: 1
  }]
  },
  options: {
  title: {
  display: true,
  text: 'Browser'
  }
  }
  });
  <?php $stats = getStatistic("platform"); ?>
  var ctx2 = document.getElementById("platformChart");
  var myChart2 = new Chart(ctx2, {
  type: 'doughnut',
  data: {
  labels: <?php echo json_encode($stats["labels"]); ?>,
  datasets: [{
  data: <?php echo json_encode($stats["data"]); ?>,
  backgroundColor: [
  'rgba(255, 99, 132, 0.2)',
  'rgba(54, 162, 235, 0.2)',
  'rgba(255, 206, 86, 0.2)',
  'rgba(75, 192, 192, 0.2)',
  ],
  borderColor: [
  'rgba(255,99,132,1)',
  'rgba(54, 162, 235, 1)',
  'rgba(255, 206, 86, 1)',
  'rgba(75, 192, 192, 1)',
  ],
  borderWidth: 1
  }]
  },
  options: {
  title: {
  display: true,
  text: 'Platform'
  }
  }
  });
  <?php $stats = getStatistic("daily_clicks"); ?>
  var myChart3 = new Chart(document.getElementById("daily_clicks"), {
  type: 'line',
  data: {
  labels: <?php echo json_encode($stats["labels"]); ?>,
  datasets: [{
  data: <?php echo json_encode($stats["data"]); ?>,
  label: 'Tägliche Seitenaufrufe',
  fill: false,
  backgroundColor: 'rgba(54, 162, 235, 1)',
  borderColor: 'rgba(54, 162, 235, 1)'
  }]
  },
  options: {
  responsive: true,
  maintainAspectRatio: false,
  title: {
  display: true,
  text: 'Tägliche Seitenaufrufe'
  },
  tooltips: {
  mode: 'index',
  intersect: false,
  },
  hover: {
  mode: 'nearest',
  intersect: true
  },
  scales: {
  xAxes: [{
  display: true,
  scaleLabel: {
  display: true,
  labelString: 'Tag'
  }
  }],
  yAxes: [{
  display: true,
  scaleLabel: {
  display: true,
  labelString: 'Anzahl Seitenaufrufe'
  },
  ticks: {
  beginAtZero: true
  }
  }]
  }
  }
  });
  <?php $stats = getStatistic("daily_new_visitors"); ?>
  var myChart4 = new Chart(document.getElementById("daily_new_visitors"), {
  type: 'line',
  data: {
  labels: <?php echo json_encode($stats["labels"]); ?>,
  datasets: [{
  data: <?php echo json_encode($stats["data"]); ?>,
  label: 'Tägliche neue Seitenbesucher',
  fill: false,
  backgroundColor: 'rgba(54, 162, 235, 1)',
  borderColor: 'rgba(54, 162, 235, 1)'
  }]
  },
  options: {
  responsive: true,
  maintainAspectRatio: false,
  title: {
  display: true,
  text: 'Tägliche neue Seitenbesucher'
  },
  tooltips: {
  mode: 'index',
  intersect: false,
  },
  hover: {
  mode: 'nearest',
  intersect: true
  },
  scales: {
  xAxes: [{
  display: true,
  scaleLabel: {
  display: true,
  labelString: 'Tag'
  }
  }],
  yAxes: [{
  display: true,
  scaleLabel: {
  display: true,
  labelString: 'Anzahl neue Seitenbesucher'
  },
  ticks: {
  beginAtZero: true
  }
  }]
  }
  }
  });
</script>
</body>
</html>
