(function () {
    $(document).ready(function () {

        var toggleDialog = function () {
            $(document.body).toggleClass("overlay-backdrop");
            $("#generator").toggleClass("hidden");
        };

        $("#button-close").click(toggleDialog);

        $("table").each(function (i, table) {
            var headers = $(table).children("thead").children("tr").children("th").map(function (index, th) {
                return $(th).text();
            });

            $(table).children("tbody").children("tr.entry").each(function (index, tr) {
                var cells = $(tr).children("td");

                cells.first().prepend($("<a>").text("[dok]").attr("href", "#").click(function (e) {

                    var paramContainer = $("#parameters");
                    paramContainer.empty();

                    var cells = $(this).parent().parent().children("td");
                    for (var x = 0; x < cells.length; x++) {
                        var td = cells[x];

                        var paramName = headers[x].toUpperCase();
                        var paramValue = td.innerText;

                        paramContainer.append(
                            $("<div/>")
                                .addClass("form-group")
                                .append($("<label/>")
                                    .text(paramName)
                                    .attr("for", "field-" + paramName)
                                    .addClass("col-sm-4 control-label"))

                                .append($("<div/>")
                                    .addClass("col-sm-8")
                                    .append($("<input/>")
                                        .attr("type", "text")
                                        .attr("name", paramName)
                                        .attr("id", "field-" + paramName)
                                        .val(paramValue)
                                        .addClass("form-control"))
                            )
                        );
                    }

                    toggleDialog();
                }));
            });
        });
    });
})();