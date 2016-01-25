/**
 * @file Launch date pupup
 * @author Denis Chenu
 * @copyright Denis Chenu <http://www.sondages.pro>
 * @license magnet:?xt=urn:btih:1f739d935676111cfff4b4693e3816e664797050&dn=gpl-3.0.txt GPL-v3-or-Later
 * @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL v3.0
 * @license magnet:?xt=urn:btih:d3d9a9a6595521f9666a5e94cc830dab83b65699&dn=expat.txt Expat (MIT)
 */

function doRegisterDate(sElement,jsonOption)
{
    console.log(jsonOption.mindate);
    console.log(jsonOption.maxdate);

    $("#"+sElement).datepicker({
        showOn: 'both',
        changeYear: true,
        changeMonth: true,
        defaultDate: +0,
        minDate: new Date(jsonOption.mindate),
        maxDate:  new Date(jsonOption.maxdate),
        firstDay: "1",
        duration: 'fast',
        dateFormat: jsonOption.sdateFormat,
        // set more options at "runtime"
    }, $.datepicker.regional[jsonOption.sLanguage]);
}
