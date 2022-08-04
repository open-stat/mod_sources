
var SourceTest = {
    src: 'mod/sources/v1.0.0',
    baseUrl: 'index.php?module=sources&action=test',
    currentNum: 1,

    site: {
        fieldRulesId: '',

        /**
         * @param params
         */
        init: function (params) {

            SourceTest.src               = params.src;
            SourceTest.site.fieldRulesId = params.fieldRulesId;



            let fieldRules = $('#' + params.fieldRulesId);
            fieldRules.val(localStorage.getItem('source-test-site-rules'))

            fieldRules.on('keyup', function () {
                localStorage.setItem('source-test-site-rules', fieldRules.val());
            });
        },


        /**
         *
         */
        test: function() {

            let tpl =
                '<div class="panel panel-default" id="launch-[KEY]">' +
                    '<div class="panel-heading">' +
                        '<h4 class="panel-title">' +
                            '<a data-toggle="collapse" data-parent="#container-launches" href="#collapse-[KEY]">' +
                                '[NUM]. <span class="launch-detail">Выполнение...</span>' +
                            '</a>' +
                        '</h4>' +
                    '</div>' +
                    '<div id="collapse-[KEY]" class="panel-collapse collapse in">' +
                        '<div class="panel-body">' +
                            '<img src="' + SourceTest.src + '/assets/test/img/load.gif" alt="loading"/> Загрузка <span class="count-seconds">0</span> сек.' +
                        '</div>' +
                    '</div>' +
                '</div>';

            let key = SourceTest.keygen();

            tpl = tpl.replace(/\[KEY\]/g, key);
            tpl = tpl.replace(/\[NUM\]/g, SourceTest.currentNum);


            $('#container-launches').prepend(tpl);
            $('#test-button-start').addClass('disabled').attr('disabled', 'disabled');

            SourceTest.currentNum++;


            let countSeconds = 1;
            let intervalID   = setInterval(function () {
                $('#launch-' + key + ' .count-seconds').text(countSeconds);
                countSeconds++;
            }, 1000);

            let options = {};


            if ($('#option-all-pages').is(':checked')) {
                options['load_all_pages'] = true;
            }


            $.ajax({
                type: "POST",
                url: SourceTest.baseUrl + '&data=test_site',
                data: {
                    rules: $('#' + SourceTest.site.fieldRulesId).val(),
                    options: options
                },
                success: function (response) {

                    try {
                        let data = JSON.parse(response);

                        if (data.status !== 'success') {

                            let body = '';

                            $('#launch-' + key).removeClass('panel-default').addClass('panel-danger');
                            $('#launch-' + key + ' .launch-detail').text('ОШИБКА. ' + data.error_message || 'Запрос вернул некорректные данные');
                            $('#launch-' + key + ' .panel-body').html(body || '<i>нет данных</i>');

                        } else {
                            let timeLaunch = 'Время: ' + (typeof data.time === 'number' ? data.time + ' сек' : '-');
                            let memLaunch  = 'Память: ' + (typeof data.mem === 'number'  ? data.mem + ' mb' : '-');

                            $('#launch-' + key).removeClass('panel-default').addClass('panel-success');
                            $('#launch-' + key + ' .launch-detail').text('Успешно. ' + timeLaunch + ', ' + memLaunch);
                            $('#launch-' + key + ' .panel-body').html(data.body || '<i>нет данных</i>');
                        }

                    } catch (e) {
                        $('#launch-' + key).removeClass('panel-default').addClass('panel-danger');
                        $('#launch-' + key + ' .launch-detail').text('ОШИБКА. Запрос вернул некорректные данные');
                        $('#launch-' + key + ' .panel-body').html(response || '<i>нет данных</i>');
                    }

                },
                complete: function () {
                    $('#test-button-start').removeClass('disabled').removeAttr('disabled');
                    clearInterval(intervalID);
                },
                error: function (header) {
                    $('#launch-' + key).removeClass('panel-default').addClass('panel-danger');
                    $('#launch-' + key + ' .launch-detail').text('ОШИБКА. Запрос был завершен некорректно');
                    $('#launch-' + key + ' .panel-body').html('<i>нет данных</i>');
                }
            });
        }
    }
}


;



/**
 * Генератор случайного ключа
 * @param extInt
 * @returns {*}
 */
SourceTest.keygen = function(extInt) {
    var d = new Date();
    var v1 = d.getTime();
    var v2 = d.getMilliseconds();
    var v3 = Math.floor((Math.random() * 1000) + 1);
    var v4 = extInt ? extInt : 0;

    return 'A' + v1 + v2 + v3 + v4;
}

