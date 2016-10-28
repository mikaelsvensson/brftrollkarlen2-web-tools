<div id="jsonForm" class="hidden overlay">
    <div class="container-fluid">
        <div class="col-sm-12">
            <h3>Anv&auml;nd Fyll i v&auml;rden-till&auml;gget i Word</h3>

            <p>S&aring; h&auml;r g&ouml;r du:</p>

            <ol>
                <li>Textf&auml;ltet nedan visar de parametrar som finns p&aring; raden, ex. LGHNR.</li>
                <li>Tryck p&aring; "Kopiera text"</li>
                <li>&Ouml;ppna Word och skapa ett dokument som inneh&aring;ller, exempelvis, texten "$LGHNR".</li>
                <li>Tryck p&aring; den gr&ouml;na knappen "Fyll i" p&aring; Infoga-fliken</li>
                <li>Ett formul&auml;r &ouml;ppnas och du kan fylla i parametrarna, ex. LGHNR. Om allt g&aring;r som det
                    ska
                    s&aring; ska v&auml;rdena fr&aring;n nedanst&aring;ende formul&auml;r automatiskt fyllas i.
                </li>
                <li>Tryck p&aring; "Ok". Parametrarna i dokumentet, ex "$LGHNR", kommer nu ers&auml;ttas med det du
                    skrev
                    in.
                </li>
            </ol>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                <textarea id="jsonData" class="form-control" style="height: 18em;"></textarea>
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group">
                <button type="button" class="btn btn-primary" id="jsonForm-button-copy">Kopiera text</button>
                <button type="button" class="btn btn-default" id="jsonForm-button-close">St&auml;ng</button>
            </div>
        </div>
    </div>
</div>
