@include('emails.partials.header')
  <table style="border-bottom: 1px solid #d8d8d8; border-collapse: collapse; border-spacing: 0; display: table; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%;" class="row">
    <tbody>
      <tr style="padding: 0; text-align: left; vertical-align: top;">
        <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; padding-bottom: 30px; text-align: left; vertical-align: top; word-wrap: break-word;">
          <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;">Hi {{$data['fullname']}},</p>
          <br>
          <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;">
            @if(!empty($data['reset_msg']))
              {!! $data['reset_msg'] !!}
            @else
              We have received your request to change your password. If you did not make this request, please contact our customer support.
            @endif
          </p>
        </td>
      </tr>
    </tbody>
  </table>
  <table class="button rounded expanded" style="Margin: 0 0 16px 0; border-collapse: collapse; border-spacing: 0; margin: 0 0 16px 0; padding: 0; text-align: left; vertical-align: top; width: 100% !important;margin-bottom:50px;">
    <tbody>
      <tr style="padding: 0; text-align: left; vertical-align: top;">
        <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
          <table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
            <tbody>
              <tr style="padding: 0; text-align: left; vertical-align: top;">
                <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; background: #2b2b2b; border: none; border-collapse: collapse !important; border-radius: 500px; color: #fefefe; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 10px; text-align: left; vertical-align: top; word-wrap: break-word;">
                  <a href="{{$data['reset_url']}}" align="center" class="float-center" style="Margin: 0; border: 0 solid #2199e8; border-radius: 3px; color: #fefefe; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: 600; line-height: 1.3; margin: 0; padding: 8px 16px 8px 16px; padding-left: 0; padding-right: 0; text-align: center; text-decoration: none; width: 100%;text-transform: uppercase;">Reset Password</a>
                </td>
              </tr>
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
  </table>
@include('emails.partials.footer')