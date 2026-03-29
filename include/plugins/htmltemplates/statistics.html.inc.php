<h1>Statistiken</h1>
	<?php
	echo getAll();
	?>
	<br><br>
	<?php
	echo getUsers();
	?>
	<br><br>
	<div style="overflow: hidden;">
		<div class="piechart"><canvas id="platformChart" width="400" height="400"></canvas></div>
		<div class="piechart"><canvas id="browserChart" width="400" height="400"></canvas></div>
	</div>
    <div class="linechart"><canvas id="daily"></canvas></div>
    <div class="linechart"><canvas id="daily_visitors"></canvas></div>
    <script>
				<?php $stats = getStatistic("browsername"); ?>
                var ctx = document.getElementById("browserChart");
                var myChart = new Chart(ctx, {
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
                <?php $stats = getStatistic("daily"); ?>
                var myChart3 = new Chart(document.getElementById("daily"), {
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

                <?php $stats = getStatistic("daily_visitors"); ?>
                var myChart4 = new Chart(document.getElementById("daily_visitors"), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($stats["labels"]); ?>,
                        datasets: [{
                            data: <?php echo json_encode($stats["data"]); ?>,
                            label: 'Tägliche Seitenbesucher',
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
                            text: 'Tägliche Seitenbesucher'
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
                                    labelString: 'Anzahl Seitenbesucher'
                                },
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
    </script>
