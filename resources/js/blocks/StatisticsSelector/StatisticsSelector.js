/**
 * Media Image Block
 */

//require('./Statistics.css').toString();
import './Statistics.css';
//const axios = require('axios');
import axios from 'axios';
//const ApexCharts = require('apexcharts');
import ApexCharts from 'apexcharts';

class Statistics {
    constructor({data, api, config}) {
        this.api = api;
        this.config = config || {};
        this.data = data;
        this.data = {
            id: data.id || '',
            title: data.title || '',
        };

        this.wrapper = undefined;
    }

    static get toolbox() {
        return {
            title: 'Statistics',
            icon: '<svg style="width:24px;height:24px" viewBox="0 0 24 24">\n' +
                '    <path fill="currentColor" d="M16,6L18.29,8.29L13.41,13.17L9.41,9.17L2,16.59L3.41,18L9.41,12L13.41,16L19.71,9.71L22,12V6H16Z" />\n' +
                '</svg>'
        };
    }

    /**
     * Automatic sanitize config
     */
    static get sanitize() {
        return {
            title: false,
            id: false,
        }
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('media-image');
        var _this = this;

        const image_wrapper = document.createElement('div');
        image_wrapper.classList.add('image-wrapper');

        const statisticLink = document.createElement('a');
        image_wrapper.appendChild(statisticLink);

        const statisticChart = document.createElement('div');
        statisticChart.setAttribute('class', 'apexchart')
        this.wrapper.appendChild(statisticChart);

        const media_btn = document.createElement('div');
        media_btn.classList.add(this.api.styles.button);
        media_btn.innerHTML = this.data && this.data.url ? 'Change Selected Statistic' : 'Select A Statistics';
        image_wrapper.appendChild(media_btn);

        this.wrapper.appendChild(image_wrapper);

        const input_wrapper = document.createElement('div');
        input_wrapper.classList.add('input-wrapper');

        var media_picker_url = window.Laravel.statisticsPicker;

        media_btn.addEventListener('click', (e) => {
            e.preventDefault();

            let dialog = bootbox.dialog({
                title: 'Statistics Selector',
                backdrop: true,
                message: '<p><i class="loading"></i> Loading...</p>',
                size: 'lg',
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        className: 'btn-light',
                        callback: function () {
                            //
                        }
                    },
                    ok: {
                        label: 'Select Statistic',
                        className: 'btn-primary',
                        callback: function () {
                            let iframe = $(this).find('iframe').contents();
                            let statisticsPicker = iframe.find('.statistics-picker');
                            let selected = statisticsPicker.find('.select2-basic :selected').first();

                            if (!selected) {
                                $(this).find('.alert').show();
                                return false;
                            } else {
                                let stat_id = selected.val();
                                let stat_title = selected.text();

                                // assign image
                                _this.data.id = stat_id;
                                _this.data.title = stat_title;

                                media_btn.innerHTML = 'Change Statistic';
                                _this._createImage(_this.wrapper, _this.data.id, _this.data.title);

                                $(this).find('.alert').hide();
                            }
                        }
                    }
                }
            });

            dialog.init(function () {
                let html =
                    '<div class="alert alert-danger" style="display: none">Please select a file!</div>' +
                    '<div class="embed-responsive embed-responsive-16by9">' +
                    '<iframe class="embed-responsive-item" src="' + media_picker_url + '"></iframe>' +
                    '</div>';

                dialog.find('.bootbox-body').html(html);
            });

        });

        if (this.data && this.data.title) {
            this._createImage(this.wrapper, this.data.id, this.data.title);
        }

        return this.wrapper;
    }

    _createImage(inputWrapper, id, title) {
        const statisticLink = this.wrapper.querySelector('a');
        statisticLink.setAttribute('data-id', id);

        let _renderChartArea = function (inputWrapper) {
            const statisticChart = document.createElement('div');
            statisticChart.setAttribute('class', 'apexchart')
            inputWrapper.prepend(statisticChart);
        }

        axios.get(window.Laravel.statisticsApi, {
            params: {
                statistic: `${id}`
            }
        })
            .catch(function () {
                console.error('Error: Could not make ajax request.')
            })
            .then(function (result) {
                const response_data = result.data;
                const chart_name = response_data.name;
                const data = response_data.data;

                statisticLink.text = chart_name;

                inputWrapper.querySelector('.apexchart').remove();

                _renderChartArea(inputWrapper);

                const chart_area = inputWrapper.querySelector('.apexchart');
                var chart = new ApexCharts(chart_area, data);

                if (chart.ohYeahThisChartHasBeenRendered) {
                    chart.ohYeahThisChartHasBeenRendered = false;
                    chart.destroy()
                }

                chart.render().then(() => chart.ohYeahThisChartHasBeenRendered = true);
            })
    }

    save(blockContent) {
        const statistic = blockContent.querySelector('a');

        return Object.assign(this.data, {
            title: statistic.text,
            id: statistic.getAttribute('data-id'),
        });
    }

    validate(savedData) {
        return savedData.id.trim() || savedData.title.trim();
    }

    renderSettings() {
        const wrapper = document.createElement('div');
        return wrapper;
    }
}

export default  Statistics;
