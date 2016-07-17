//parseFloat("1 524 768,10".replace(/\s/g, "1").replace(",","."))
//115241768.1

(function () {
    function initGraph(tableId, columnId, el, width, height, labelFilter) {
        height = height || 400;
        width = width || 300;
        var values = [];
        $("td.data-value.table-" + tableId + ".column-" + columnId).each(function (index) {
            var cell = $(this);
            var raw = cell.text();
            var value = parseFloat(raw.replace(/\s/g, "").replace(",", "."));
            console.log("value " + index + ": " + value);
            var text = cell.siblings("th").text();
            if (!labelFilter || labelFilter(text)) {
                values.push([text, value]);
            }

        });

        // Create the data table.
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Rapport');
        data.addColumn('number', 'Matvarde');
        data.addRows(values);

        // Set chart options
        var options = {
            width: width,
            height: height,
            legend: {position: "none"},
            axisTitlesPosition: "none",
            colors: ['#84BB61']
        };

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.ColumnChart(el);
        chart.draw(data, options);
    }

    var initGraphs = function () {
        $("div.table-placeholder").each(function (index) {
            var el = $(this);
            var tableId = el.attr("data-table");
            var columnId = el.attr("data-column");
            console.log(tableId, columnId);

            initGraph(tableId, columnId, el.get()[0], 300);
        });

        initGraph("", "1", $("div#overall").get()[0], 1500, 600, function (text) {
            return text.match(/Summa/);
        });

    };

    $(document).ready(function () {
        google.charts.load('current', {'packages': ['corechart', 'bar']});

        // Set a callback to run when the Google Visualization API is loaded.
        google.charts.setOnLoadCallback(initGraphs);
    });


})();