cat <<EOF | docker run -i --rm \
  -v $HOME/kroki-transformer.js:/opt/phpdoc/data/templates/default/kroki-transformer.js.twig:ro \
  -v $HOME/project/:/data \
  --entrypoint=sh phpdoc/phpdoc:3

sed -i "/<\/transformations>/i <transformation writer=\"twig\" \
  source=\"templates/default/kroki-transformer.js.twig\" \
    artifact=\"js/kroki-transformer.js\" />" \
  /opt/phpdoc/data/templates/default/template.xml
sed -i "/{% block javascripts %}/a <script \
  src=\"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js\"> \
  </script> <script src=\"js/kroki-transformer.js\"></script>" \
  /opt/phpdoc/data/templates/default/layout.html.twig
/opt/phpdoc/bin/phpdoc run -d . -t docs
EOF