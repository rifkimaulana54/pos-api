@include('emails.partials.header')
      <table style="border-bottom: 1px solid #d8d8d8; border-collapse: collapse; border-spacing: 0; display: table; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%;" class="row">
        <tbody>
          <tr style="padding: 0; text-align: left; vertical-align: top;">
            <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; padding-bottom: 30px; text-align: left; vertical-align: top; word-wrap: break-word;">
              <p>
                @if(!empty($body))
                  {!! $body !!}
                @endif
              </p>
            </td>
          </tr>
        </tbody>
      </table>
@include('emails.partials.footer')