<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message;

enum ReportReason: string
{
    /** Report for spam */
    case SPAM = 'inputReportReasonSpam';
    /** Report for violence */
    case VIOLENCE = 'inputReportReasonViolence';
    /** Report for pornography */
    case PORNOGRAPHY = 'inputReportReasonPornography';
    /** Report for child abuse */
    case CHILD_ABUSE = 'inputReportReasonChildAbuse';
    /** Report for copyrighted content */
    case COPYRIGHT = 'inputReportReasonCopyright';
    /** Report an irrelevant geogroup */
    case GEO_IRRELEVANT = 'inputReportReasonGeoIrrelevant';
    /** Report for impersonation */
    case FAKE = 'inputReportReasonFake';
    /** Report for illegal drugs */
    case ILLEGAL_DRUGS = 'inputReportReasonIllegalDrugs';
    /** Report for divulgation of personal details */
    case PERSONAL_DETAILS = 'inputReportReasonPersonalDetails';
    /** Other */
    case OTHER = 'inputReportReasonOther';
}
