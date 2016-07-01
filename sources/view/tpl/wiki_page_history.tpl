<table class="table-striped table-responsive table-hover" style="width: 100%;">
  {{foreach $pageHistory as $commit}}
  <tr><td>
      <table id="rev-{{$commit.hash}}" onclick="$('#details-{{$commit.hash}}').show()" width="100%">
        <tr><td width="10%">Date</td><td width="70%">{{$commit.date}}</td><td rowspan="3" width="20%" align="right">
            <button id="revert-{{$commit.hash}}" class="btn btn-danger btn-xs" onclick="wiki_revert_page('{{$commit.hash}}')">Revert</button></td></tr>
        <tr><td>Name</td><td>{{$commit.name}} &lt;{{$commit.email}}&gt;</td></tr>
        <tr><td>Message</td><td>{{$commit.title}}</td></tr>
      </table>
    </td></tr>
  {{/foreach}}          
</table>