(function () {
    $(document).ready(function () {
        $("table").each (function (i, table) {
            var headers = $(table).children("thead").children("tr").children("th").map(function (index, th) {
                return $(th).text();
            });

            $(table).children("tbody").children("tr.entry").each(function (index, tr) {
                var cells = $(tr).children("td");
                var firstCell = cells[0];
                var values = cells.map(function (x, td) {
                    return headers[x].toUpperCase() + "=" + encodeURIComponent(td.innerHTML);
                }).get().join("&");

                cells.first().prepend($("<a>").text("[dok]").attr("href", "google-document-generator.php?" + values));
            });
        });
    });
})();