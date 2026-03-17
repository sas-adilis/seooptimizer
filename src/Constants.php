<?php

namespace Adilis\SeoOptimizer;

class Constants
{
    const HTTP_CODE_301 = 301;
    const HTTP_CODE_302 = 302;

    const PAGE_INDEXATION_DO_NOTHING = 0;
    const PAGE_INDEXATION_NOINDEX = 1;
    const PAGE_INDEXATION_404 = 2;
    const PAGE_INDEXATION_REDIRECT_301 = 3;
    const PAGE_INDEXATION_REDIRECT_302 = 4;

    const REPORT_PARTIAL = 'partial';
    const REPORT_COMPLETED = 'completed';
    const MAX_ELEMENTS_PER_PROCESS = 500;

    const JSON_STATUS_SUCCESS = 'success';
    const JSON_STATUS_ERROR = 'error';

    const REPORT_STATUS_PROCESSING = 'processing';
    const REPORT_STATUS_DONE = 'done';
    const REPORT_STATUS_READY_TO_PROCESS = 'ready_to_process';

    const HTML_FIELD = 'html';
    const TITLE_FIELD = 'title';
    const META_TITLE_FIELD = 'meta_title';
    const LEGEND_FIELD = 'legend';

    const RULE_TYPE_IS = 'is';
    const RULE_TYPE_CONTAINS = 'contains';
    const RULE_TYPE_STARTS_WITH = 'starts_with';

    const FIX_METHOD_TEXT = 'text';
    const FIX_METHOD_IGNORE = 'ignore';
    const FIX_METHOD_REMOVE = 'remove';
    const FIX_METHOD_IA = 'ia';
}
