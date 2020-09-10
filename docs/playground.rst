Playground
==========

Here's the playground to test out queries. You can use the OXID GraphQL Demoshop installation endpoint

::

    https://oxidgraphql.com/graphql/

or any other suitable graphql endpoint.

.. important::

   The demoshop is reset on every full hour, so it might be offline once in a while for some moments.


.. raw:: html

    <script type="text/javascript">
    <!--
    window.onload = function ()
    {
        document.getElementsByClassName('wy-nav-content')[0].style.setProperty("max-width", "100%");
    }

    setQuery = function (element)
    {
        var query = decodeURIComponent(window.location.search.substr(7));

        if ( 0 < query.length &&
            (element.getAttribute('initial-query') != query)
        ) {
            element.setAttribute('initial-query', query);
            window.frames[element.name].location.reload();
        }
    }

    //-->
    </script>
    <iframe onload="setQuery(this);" name="altair-client" id="altair-client" src="_static/altairclient/index.html" height="1000px" width="100%" graphql-url="https://oxidgraphql.com/graphql/" initial-query='query{token(username: "user@oxid-esales.com", password: "useruser")}'>
    </iframe>

