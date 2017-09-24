<?php

namespace Config;

class Report
{
    private $reportReader = null;
    private $rowProcessor = null;
    private $summaryGenerator = null;
    private $url = null;
    private $afterDownloadProcessor = null;
    private $columns = null;
    private $title = null;

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function getReportReader()
    {
        return $this->reportReader;
    }

    public function setReportReader($reportReader)
    {
        $this->reportReader = $reportReader;
    }

    public function getRowProcessor()
    {
        return $this->rowProcessor;
    }

    public function setRowProcessor($rowProcessor)
    {
        $this->rowProcessor = $rowProcessor;
    }

    public function getSummaryGenerator()
    {
        return $this->summaryGenerator;
    }

    public function setSummaryGenerator($summaryGenerator)
    {
        $this->summaryGenerator = $summaryGenerator;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getAfterDownloadProcessor()
    {
        return $this->afterDownloadProcessor;
    }

    public function setAfterDownloadProcessor($afterDownloadProcessor)
    {
        $this->afterDownloadProcessor = $afterDownloadProcessor;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }
}