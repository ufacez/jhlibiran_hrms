// Minimal analytics client script - renders demo charts using Chart.js

document.addEventListener('DOMContentLoaded', function(){
    // Attendance trend chart (real data)
    var ctx = document.getElementById('attendanceTrend');
    if (ctx && window.Chart && window.analyticsData) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.analyticsData.attendanceTrend.labels,
                datasets: [
                    {
                        label: 'Present',
                        data: window.analyticsData.attendanceTrend.present,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40,167,69,0.08)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Absent',
                        data: window.analyticsData.attendanceTrend.absent,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220,53,69,0.08)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive:true,
                maintainAspectRatio:false,
                plugins:{
                    legend:{display:true,position:'top'},
                    title:{display:false}
                },
                scales:{
                    y:{beginAtZero:true}
                }
            }
        });
    }

    // OT breakdown chart (real data)
    var ctx2 = document.getElementById('otBreakdown');
    if (ctx2 && window.Chart && window.analyticsData) {
        new Chart(ctx2, {
            type:'doughnut',
            data:{
                labels: window.analyticsData.otBreakdown.labels,
                datasets:[{
                    data: window.analyticsData.otBreakdown.data,
                    backgroundColor:['#ffc107','#17a2b8','#6f42c1']
                }]
            },
            options:{
                responsive:true,
                maintainAspectRatio:false,
                plugins:{
                    legend:{display:true,position:'bottom'},
                    title:{display:false}
                }
            }
        });
    }
});
