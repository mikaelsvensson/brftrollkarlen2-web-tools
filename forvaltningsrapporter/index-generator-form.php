<div id="generator" class="hidden overlay">
    <div class="container-fluid">
        <form action="google-document-generator.php" method="get" target="_blank" class="form-horizontal">
            <?php
            $drive_service = new Google_Service_Drive($client);
            $files_list = $drive_service->files->listFiles(array(
                "q" => "mimeType = 'application/vnd.google-apps.document' and trashed = false",
                "fields" => "files(appProperties,id,name),kind,nextPageToken"
            ))->getFiles();
            ?>
            <h3>Vilken mall vill du anv&auml;nda?</h3>

            <div class="form-group">
                <label for="template" class="col-sm-4 control-label">Mall</label>

                <div class="col-sm-8">
                    <select name="template" class="form-control">
                        <?php
                        foreach ($files_list as $file) {
                            if (!$file->getAppProperties()['sourceTemplate']) {
                                printf('<option value="%s">%s</option>', $file->getId(), $file->getName());
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <h3>Vad ska det st&aring; i dokumentet?</h3>
            <div id="parameters"></div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary">Skapa dokument</button>
                    <button type="button" class="btn btn-default" id="button-close">St&auml;ng</button>
                </div>
            </div>
            <div>
                <p>Du kan ocks&aring; anv&auml;nda dessa parametrar i ditt dokument:</p>
                <ul>
                    <?php
                    foreach ($defaultParams as $key => $value) {
                        printf('<li><code>%s</code>: %s</li>', $key, $value);
                    }
                    ?>
                </ul>
            </div>
        </form>
    </div>
</div>
