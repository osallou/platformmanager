<nav class="navbar navbar-default sidebar" role="navigation">
    <div class="container-fluid">

        <div class="navbar-header" style="background-color: {{space.color}};">
            <a href="corespace/{{space.id}}"><h3 style="text-align: center; font-size: 16px; color: #fff;">{{space.name}}</h3></a>
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-sidebar-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>      
        </div>
        <div class="collapse navbar-collapse" id="bs-sidebar-navbar-collapse-1">
            <ul class="nav navbar-nav">
                {{menuitems}}
                
                {{adminitems}}
            </ul>
        </div>
    </div>
</nav>