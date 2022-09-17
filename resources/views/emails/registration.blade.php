@include('emails.partials.header')
      <table style="border-bottom: 1px solid #d8d8d8; border-collapse: collapse; border-spacing: 0; display: table; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%;" class="row">
        <tbody>
          <tr style="padding: 0; text-align: left; vertical-align: top;">
            <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; padding-bottom: 30px; text-align: left; vertical-align: top; word-wrap: break-word;">
              <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;">Hi {{$data['fullname']}},</p>
              <br>
              <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;">
                @if(!empty($data['welcome_msg']))
                  {!! $data['welcome_msg'] !!}
                @else
                  Your account has been successfully created. Please take a moment to verify your email by clicking confirmation button below.
                @endif
              </p>
            </td>
          </tr>
        </tbody>
      </table>
      @if(!empty($data['confirmation_url']))      
        <table class="button rounded expanded" style="Margin: 0 0 16px 0; border-collapse: collapse; border-spacing: 0; margin: 0 0 16px 0; padding: 0; text-align: left; vertical-align: top; width: 100% !important;">
          <tbody>
            <tr style="padding: 0; text-align: left; vertical-align: top;">
              <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                <table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
                  <tbody>
                    <tr style="padding: 0; text-align: left; vertical-align: top;">
                      <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; background: #27AE60; border: none; border-collapse: collapse !important; border-radius: 500px; color: #fefefe; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 10px; text-align: left; vertical-align: top; word-wrap: break-word;margin-bottom:50px;">
                        <a href="{{$data['confirmation_url']}}" align="center" class="float-center" style="Margin: 0; border: 0 solid #2199e8; border-radius: 3px; color: #fefefe; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: 600; line-height: 1.3; margin: 0; padding: 8px 16px 8px 16px; padding-left: 0; padding-right: 0; text-align: center; text-decoration: none; width: 100%;text-transform: uppercase;">Verify Your Email</a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      @endif
@include('emails.partials.footer')