<div id="exporterForm" class="hidden overlay">
    <div class="container-fluid">
        <form action="index-export-view.php" method="post" target="_blank" class="form-horizontal">
            <h3>Exportera data som CSV-fil</h3>

            <input type="hidden" name="data" id="exporter-data">

            <div class="form-group">
                <div class="col-sm-12">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="add_headers" value="true" checked="checked">
                            Rubrikrad
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="add_row_number" value="true">
                            Radnummer p&aring; varje rad
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="add_unique_id" value="true">
                            Unikt id p&aring; varje rad
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="add_threeword_password" value="true">
                            L&ouml;senord med tre ord p&aring; varje rad
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="add_alphanumeric_password" value="true">
                            L&ouml;senord med sv&aring;rf&ouml;rv&auml;xlade bokst&auml;ver p&aring; varje rad
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-12">
                    <button type="submit" class="btn btn-primary">Exportera</button>
                    <button type="button" class="btn btn-default" id="exporterForm-button-close">St&auml;ng</button>
                </div>
            </div>
        </form>
    </div>
</div>
