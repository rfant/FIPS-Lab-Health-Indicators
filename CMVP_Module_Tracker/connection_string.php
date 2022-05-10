
$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//$connStr = "host=postgres.aus.atsec  dbname=fantDatabase user=richard password==uwXg9Jo'5Ua connect_timeout=5 options='--application_name=$appName'";
$connStr = "host=localhost  dbname=postgres user=postgres password=postgres connect_timeout=5 options='--application_name=$appName'";

