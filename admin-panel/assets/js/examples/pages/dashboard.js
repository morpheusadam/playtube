$(function () {

    var colors = {
        primary: $('.colors .bg-primary').css('background-color').replace('rgb', '').replace(')', '').replace('(', '').split(','),
        secondary: $('.colors .bg-secondary').css('background-color').replace('rgb', '').replace(')', '').replace('(', '').split(','),
        info: $('.colors .bg-info').css('background-color').replace('rgb', '').replace(')', '').replace('(', '').split(','),
        success: $('.colors .bg-success').css('background-color').replace('rgb', '').replace(')', '').replace('(', '').split(','),
        danger: $('.colors .bg-danger').css('background-color').replace('rgb', '').replace(')', '').replace('(', '').split(','),
        warning: $('.colors .bg-warning').css('background-color').replace('rgb', '').replace(')', '').replace('(', '').split(','),
    };

    var rgbToHex = function (rgb) {
        var hex = Number(rgb).toString(16);
        if (hex.length < 2) {
            hex = "0" + hex;
        }
        return hex;
    };

    var fullColorHex = function (r, g, b) {
        var red = rgbToHex(r);
        var green = rgbToHex(g);
        var blue = rgbToHex(b);
        return red + green + blue;
    };

    colors.primary = '#' + fullColorHex(colors.primary[0], colors.primary[1], colors.primary[2]);
    colors.secondary = '#' + fullColorHex(colors.secondary[0], colors.secondary[1], colors.secondary[2]);
    colors.info = '#' + fullColorHex(colors.info[0], colors.info[1], colors.info[2]);
    colors.success = '#' + fullColorHex(colors.success[0], colors.success[1], colors.success[2]);
    colors.danger = '#' + fullColorHex(colors.danger[0], colors.danger[1], colors.danger[2]);
    colors.warning = '#' + fullColorHex(colors.warning[0], colors.warning[1], colors.warning[2]);

    // $('#recent-orders').DataTable({
    //     lengthMenu: [5, 10],
    //     "columnDefs": [{
    //         "targets": 5,
    //         "orderable": false
    //     }]
    // });

    

    

    

    function hotProducts() {
        if ($('#hot-products').length) {
            var options = {
                series: [44, 55, 13, 36, 30],
                chart: {
                    type: 'radialBar',
                    fontFamily: "Inter",
                    offsetY: 30,
                    height: 400
                },
                colors: [colors.primary, colors.secondary, colors.success, colors.warning, colors.danger],
                labels: ['Iphone', 'Samsung', 'Huawei', 'General Mobile', 'Xiaomi'],
                dataLabels: {
                    enabled: false,

                },
                track: {
                    background: "#cccccc"
                },
                plotOptions: {
                    radialBar: {
                        track: {
                            background: $('body').hasClass('dark') ? "#344164" : "#ffffff",
                        },
                        dataLabels: {
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function (w) {
                                    return 174
                                }
                            }
                        }
                    }
                },
                legend: {
                    show: false
                }
            };

            var chart = new ApexCharts(document.querySelector("#hot-products"), options);
            chart.render();
        }
    }

    hotProducts();

    // function activityChart() {
    //     if ($('#ecommerce-activity-chart').length) {
    //         var options = {
    //             chart: {
    //                 type: 'bar',
    //                 fontFamily: "Inter",
    //                 toolbar: {
    //                     show: false
    //                 }
    //             },
    //             series: [{
    //                 name: 'Comments',
    //                 data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
    //             }, {
    //                 name: 'Product View',
    //                 data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
    //             }],
    //             colors: [colors.secondary, colors.info],
    //             plotOptions: {
    //                 bar: {
    //                     horizontal: false,
    //                     columnWidth: '50%',
    //                     endingShape: 'rounded'
    //                 },
    //             },
    //             dataLabels: {
    //                 enabled: false
    //             },
    //             stroke: {
    //                 show: true,
    //                 width: 8,
    //                 colors: ['transparent']
    //             },
    //             grid: {
    //                 show: false,
    //                 padding: {
    //                     left: 0,
    //                     right: 0
    //                 }
    //             },
    //             xaxis: {
    //                 labels: {
    //                     show: false,
    //                 },
    //                 axisBorder: {
    //                     show: false,
    //                 }
    //                 // categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
    //             },
    //             yaxis: {
    //                 show: false,
    //             },
    //             fill: {
    //                 opacity: 1
    //             },
    //             legend: {
    //                 show: false
    //             }
    //         };

    //         if ($(window).width() > 992) {
    //             options.chart.height = 395;
    //         }

    //         var chart = new ApexCharts(
    //             document.querySelector("#ecommerce-activity-chart"),
    //             options
    //         );

    //         chart.render();
    //     }
    // }

    // activityChart();

    $(window).on('load', function () {
        setTimeout(function () {
            $('#exampleModal').modal('show');
        }, 1000);
    });

});
