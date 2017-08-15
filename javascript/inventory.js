Inventory = Backbone.View.extend({
    el: '.inventory_container',

    initialize: function () {
        this.initializeDataTable();
        this.reloadPage();
        this.updateInventoryButton()
        this.pricingHistoryModalClose()
    },

    events: {
        'click .update_inventory': 'updateInventory',
        'click .edit_product': 'renderEditProductModal',
        'click .pricing_history' : 'renderPricingHistoryModal'
    },

    /**
     * Instance of Datatables for displaying product data. This is the main table you will see once you get to the page
     * is initialize on page load.
     */
    initializeDataTable: function() {
        $('.products_table').DataTable({
            dom:
                "<'row'<'col-sm-3'l><'col-sm-6 text-center'B><'col-sm-3'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            pageLength: 10,
            lengthMenu: [[10, 20, 50, -1], [10, 20, 50, "All"]],
            processing: true,
            serverSide: false,
            responsive: true,
            ajax: {
                url: 'amazon-product-inventory/get-inventory',
                type: 'GET'
            },
            columns : [
                {
                    data: 'product.amaz_product_id',
                    width: '5%'
                },
                {
                    data: 'product.amaz_seller_sku',
                    width: '10%'
                },
                {
                    data: 'product.amaz_item_name',
                    width: '30%'
                },
                {
                    data: 'pricing_template.name',
                    defaultContent: '<i>Not set</i>',
                    width: '15%'
                },
                {
                    data: 'amaz_price_submitted_at',
                    width: '15%',
                    render: function(data, index, full) {
                        var formatted_date = 'N/A';
                        var timezone = '';

                        if(data != null) {
                            formatted_date = moment(data, 'YYYY-MM-DD HH:mm:ss').format('MM/DD/YYYY hh:mm A');
                            timezone = '(UTC-06:00)'
                        }

                        var template = _.template($('#last_price_update_template').html());

                        return template({
                            formatted_date: formatted_date,
                            price: full['price'],
                            timezone: timezone
                        });
                    }
                },
                {
                    data: 'product_id',
                    width: '25%',
                    render: function(data, index, full) {
                        var template = _.template($('#edit_product_button_template').html());

                        return template({full: full});
                    }
                }
            ]
        });
    },

    // Purpose of reloading the page is to display any new products added to inventory by the cron that runs every 5 mins
    reloadPage: function() {
        setTimeout(function(){
            window.location.reload(1);
        }, 100000);
    },

    /**
     *  Function for the click event of the 'Update Inventory' button. An ajax request which directs to the updateInventory()
     *  method of the InventoryController class
     */
    updateInventory: function(e) {
        var template = $('#update_in_progress_button_template').html();
        $('.update_inventory_button_container').html(template);

        $.ajax({
            url: 'amazon-product-inventory/update-inventory',
            type: 'GET',
            data: {
                'user_id' : $(e.currentTarget).data('user_id')
            },
            error: function() {
                $('#update_inventory_button_template').html();
                $('.update_inventory_button_container').html(template);
            }
        })
    },

    updateInventoryButton: function() {
        $pending_update = $('.update_inventory_button_container').data('pending_update');

        var button_template = $('#update_inventory_button_template').html();

        if($pending_update == 0) {
            var button_template = $('#update_in_progress_button_template').html();

            var text_template = $('#update_in_progress_text_template').html();

            $('.update_inventory_text_container').html(text_template);
        }

        $('.update_inventory_button_container').html(button_template);
    },

    /**
     *  Function for the click event of the 'Edit Pricing' button. An ajax request which directs to the getProduct()
     *  method of the InventoryController class
     */
    renderEditProductModal: function(e) {
        $.ajax({
            url: 'amazon-product-inventory/get-product',
            type: 'GET',
            context: this,
            data: {
                'product_id' : $(e.currentTarget).data('product_id')
            },
            success: function(data) {
                var template = _.template($('#edit_product_form_template').html());

                $('.edit_product_form_container').html(template({data: data}));

                /**
                 * Pass the product data into the renderPricingChart which renders out the chart for comparing current pricing
                 * against amazon
                 */
                this.renderPricingChart(data);
            }
        })
    },

    /**
     *  Function for the click event of the 'Pricing History' button. An ajax request which directs to the getPricingHistory()
     *  method of the InventoryController class.
     */
    renderPricingHistoryModal: function(e) {
        var amaz_item_name = $(e.currentTarget).data('amaz_item_name');

        $('.pricing_history_amaz_item_name').text(amaz_item_name);

        // Datatable for displaying the Pricing History of a product. This table is rendered into a popup modal
        $('#product_history_datatable').DataTable({
            dom:
            "<'row'<'col-sm-3'><'col-sm-6 text-center'B><'col-sm-3'>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'><'col-sm-7'p>>",
            pageLength: 10,
            lengthMenu: [[10, 20, 50, -1], [10, 20, 50, "All"]],
            processing: true,
            serverSide: false,
            responsive: true,
            ajax: {
                url: 'amazon-product-inventory/get-pricing-history',
                data: {
                    'product_id' : $(e.currentTarget).data('product_id')
                },
                type: 'GET'
            },
            columns : [
                {
                    data: 'created_at',
                    width: '40%',
                    render: function(data, index, full) {
                        return moment(data, 'YYYY-MM-DD HH:mm:ss').format('MM/DD/YYYY hh:mm A');
                    }
                },
                {
                    data: 'old_value',
                    width: '30%',
                    render: function(data, index, full) {
                        var old_value = parseFloat(data)
                        return '$' + old_value.toFixed(2);
                    }
                },
                {
                    data: 'new_value',
                    width: '30%',
                    render: function(data, index, full) {
                        var new_value = parseFloat(data)
                        return '$' + new_value.toFixed(2);
                    }
                }
            ]
        });
    },

    /**
     * Chart for displaying the user's current product pricing against Amazon
     *
     * @param data
     */
    renderPricingChart: function(data) {
        var ctx = document.getElementById("pricing_chart").getContext("2d");

        var barChartData = {
            labels: ['Your Price', 'Buy Box'],
            datasets: [{
                data: [data.pivot.price, data.buy_box],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                borderColor: [
                    'rgba(255,99,132,1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1,
            }]
        }

        Chart.pluginService.register({
            beforeRender: function (chart) {
                if (chart.config.options.showAllTooltips) {
                    // create an array of tooltips
                    // we can't use the chart tooltip because there is only one tooltip per chart
                    chart.pluginTooltips = [];
                    chart.config.data.datasets.forEach(function (dataset, i) {
                        chart.getDatasetMeta(i).data.forEach(function (sector, j) {
                            chart.pluginTooltips.push(new Chart.Tooltip({
                                _chart: chart.chart,
                                _chartInstance: chart,
                                _data: chart.data,
                                _options: chart.options.tooltips,
                                _active: [sector]
                            }, chart));
                        });
                    });

                    // turn off normal tooltips
                    chart.options.tooltips.enabled = false;
                }
            },
            afterDraw: function (chart, easing) {
                if (chart.config.options.showAllTooltips) {
                    // we don't want the permanent tooltips to animate, so don't do anything till the animation runs atleast once
                    if (!chart.allTooltipsOnce) {
                        if (easing !== 1)
                            return;
                        chart.allTooltipsOnce = true;
                    }

                    // turn on tooltips
                    chart.options.tooltips.enabled = true;
                    Chart.helpers.each(chart.pluginTooltips, function (tooltip) {
                        tooltip.initialize();
                        tooltip.update();
                        // we don't actually need this since we are not animating tooltips
                        tooltip.pivot();
                        tooltip.transition(easing).draw();
                    });
                    chart.options.tooltips.enabled = false;
                }
            }
        })

        var pricing_chart = new Chart(ctx, {
            type: 'bar',
            data: barChartData,
            options: {
                showAllTooltips: true,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (value, index, values) {
                                return '$' + value;
                            }
                        }
                    }],
                },
                legend: {
                    display: false
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItems, data) {
                            return "$" + tooltipItems.yLabel.toString();
                        }
                    }
                }
            }
        });
    },

    /**
     * Function for destroying the instance of DataTables when pricing history modal closes
     */
    pricingHistoryModalClose: function(){
        $('#pricing_history_modal').on('hidden.bs.modal', function () {
            $('#product_history_datatable').DataTable().destroy();
        })
    }
});

Inventory = new Inventory();
