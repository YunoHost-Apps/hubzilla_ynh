<div id="invite" class="generic-content-wrapper">
  <div class="section-title-wrapper">
    <h2>{{$invite}}</h2>
  </div>
  <div class="section-content-wrapper">

    <form action="invite" method="post" id="invite-form" >

      <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

      <div id="invite-recipient-textarea" class="form-group field custom">
        <label for="recipients">{{$addr_text}}</label>
        <textarea id="invite-recipients" name="recipients" rows="6" class="form-control"></textarea>
      </div>

      <div id="invite-message-textarea" class="form-group field custom">
        <label for="message">{{$msg_text}}</label>
        <textarea id="invite-message" name="message" rows="12" class="form-control">{{$default_message}}</textarea>
      </div>

      <div id="invite-submit-wrapper" class="form-group">
        <button class="btn btn-primary btn-sm" type="submit" id="invite-submit" name="submit" value="{{$submit}}">{{$submit}}</button>
      </div>

    </form>

  </div>
</div>
