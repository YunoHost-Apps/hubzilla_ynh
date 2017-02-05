<XML>
  <post>
    <like>
      {{if $xml}}
      {{$xml}}
      {{else}}
      <positive>{{$positive}}</positive>
      <guid>{{$guid}}</guid>
      <target_type>{{$target_type}}</target_type>
      <parent_guid>{{$parent_guid}}</parent_guid>
      <diaspora_handle>{{$handle}}</diaspora_handle>
      <author_signature>{{$authorsig}}</author_signature>
      {{/if}}
      {{if $parentsig}}
      <parent_author_signature>{{$parentsig}}</parent_author_signature>
      {{/if}}
    </like>
  </post>
</XML>
