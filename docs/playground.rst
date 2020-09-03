Playground
==========

Here's the playground to test out queries. You can use the OXID GraphQL Demoshop installation endpoint

::

    https://oxidgraphql.com/graphql/

or any other suitable graphql endpoint.


.. raw:: html

    <script type="text/javascript">
    <!--
    window.onload = function ()
    {
        document.getElementsByClassName('wy-nav-content')[0].style.setProperty("max-width", "100%");
    }
    //-->
    </script>
    <iframe id="altair-client" src="_static/altairclient/index.html" height="1000px" width="100%" graphql-url="https://oxidgraphql.com/graphql/" initial-query='query{token(username: "user@oxid-esales.com", password: "useruser")}'>
    </iframe>

