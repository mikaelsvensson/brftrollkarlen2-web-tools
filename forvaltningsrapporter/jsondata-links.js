(function () {
    $(document).ready(function () {

        var toggleDialog = function () {
            $(document.body).toggleClass("overlay-backdrop");
            $("#jsonForm").toggleClass("hidden");
        };

        var copyJson = function() {
            var copyTextarea = document.querySelector('#jsonData');
            copyTextarea.select();

            try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'successful' : 'unsuccessful';
                console.log('Copying text command was ' + msg);
            } catch (err) {
                console.log('Oops, unable to copy');
            }
        };

        $("#jsonForm-button-close").click(toggleDialog);
        $("#jsonForm-button-copy").click(copyJson);


        $("table").each(function (i, table) {
            var headers = $(table).children("thead").children("tr").children("th").map(function (index, th) {
                return $(th).text();
            });

            $(table).children("tbody").children("tr.entry").each(function (index, tr) {
                var cells = $(tr).children("td");

                cells.first().prepend($("<button/>").attr("type", "button").addClass("btn btn-default btn-xs").click(function (e) {

                    var paramContainer = $("#jsonData");
                    paramContainer.empty();

                    var data = {};

                    var cells = $(this).parent().parent().children("td");
                    for (var x = 0; x < cells.length; x++) {
                        var td = cells[x];

                        var paramName = headers[x].toUpperCase();
                        var paramValue = td.innerText;
                        data[paramName] = paramValue;
                    }

                    paramContainer.text(JSON.stringify(data, null, '    '));

                    toggleDialog();
                }).append($("<img />").attr("src","application-small-list-blue.png").addClass("glyphicon glyphicon-duplicate")));
            });
        });
    });
})();