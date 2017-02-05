<style>
  .diff {
    width:100%;
    word-break: break-all;
  }

  .diff td{
    padding:0 0.667em;
    vertical-align:top;
    white-space:pre;
    white-space:pre-wrap;
    font-family:Consolas,'Courier New',Courier,monospace;
    font-size:1.0em;
    line-height:1.333;
  }

  .diff span{
    display:block;
    min-height:1.333em;
    margin-top:-1px;
    padding:0 3px;
  }

  * html .diff span{
    height:1.333em;
  }

  .diff span:first-child{
    margin-top:0;
  }

  .diffDeleted span{
    border:1px solid rgb(255,192,192);
    background:rgb(255,224,224);
  }

  .diffInserted span{
    border:1px solid rgb(192,255,192);
    background:rgb(224,255,224);
  }
</style>
<table class="table-striped table-responsive table-hover" style="width: 100%;">
  {{foreach $pageHistory as $commit}}
  <tr><td>
      <table id="rev-{{$commit.hash}}" onclick="$('#details-{{$commit.hash}}').show()" width="100%">
        <tr><td width="10%">Date</td><td width="70%">{{$commit.date}}</td>
            <td rowspan="3" width="20%" align="right">
		{{if $permsWrite}}
              <button id="revert-{{$commit.hash}}" class="btn btn-danger btn-xs" onclick="wiki_revert_page('{{$commit.hash}}')">Revert</button>
              <br><br>
		{{/if}}
              <button id="compare-{{$commit.hash}}" class="btn btn-warning btn-xs" onclick="wiki_compare_page('{{$commit.hash}}')">Compare</button>
            </td></tr>
        <tr><td>Name</td><td>{{$commit.name}} &lt;{{$commit.email}}&gt;</td></tr>
        <tr><td>Message</td><td>{{$commit.title}}</td></tr>
      </table>
    </td></tr>
  {{/foreach}}          
</table>
