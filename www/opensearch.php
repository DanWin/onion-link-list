<?php
require_once(__DIR__.'/../common_config.php');
header('Content-Type: application/opensearchdescription+xml');
?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
  <ShortName><?php echo _('Onion link list'); ?></ShortName>
  <Description><?php echo _('Search the onion link list'); ?></Description>
  <Contact>daniel@danwin1210.de</Contact>
  <Developer>Daniel Winzen</Developer>
  <Image width="192" height="192" type="image/gif"><?php echo CANONICAL_URL; ?>/favicon.ico</Image>
  <Url type="text/html" method="get" template="<?php echo CANONICAL_URL; ?>/?q={searchTerms}" />
</OpenSearchDescription>
