function getSeason(\\DateTimeImmutable $date): string
{
    switch ($date->format('M')) {
        case 'Mar': case 'Apr': case 'May':
            return 'spring';
        case 'Jun': case 'Jul': case 'Aug':
            return 'summer';
        case 'Sep': case 'Oct': case 'Nov':
            return 'fall';
        case 'Dec': case 'Jan': case 'Feb':
            return 'winter';
        default:
            return '';
    }
}