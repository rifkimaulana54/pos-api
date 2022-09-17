@include('emails.partials.header')
  @php 
    $order = json_decode(json_encode($data['order'])); 
    // dd($order->payment_reference);
  @endphp
    <table style="border-bottom: 1px solid #d8d8d8; border-collapse: collapse; border-spacing: 0; display: table; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%;" class="row">
      <tbody>
        <tr style="padding: 0; text-align: left; vertical-align: top;">
          <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; padding-bottom: 30px; text-align: left; vertical-align: top; word-wrap: break-word;">
            <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;">Hi {{$data['fullname']}},</p>
            <br>
            <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;">
              @if(!empty($data['order_msg']))
                {!! $data['order_msg'] !!}
              @else
                This is notification for new customer order from KANA website
              @endif              
            </p>
          </td>
        </tr>
      </tbody>
    </table>
    <h2 style="color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: 600; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; padding-top:10px; text-align: center; word-wrap: normal;">Order Summary</h2>    
    <table class="row" style="background: #f2f2f2; border-collapse: collapse; border-spacing: 0; display: table; margin-bottom: 45px; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%;">
      <tbody>
        <tr style="padding: 0; text-align: left; vertical-align: top;">
          <th class="small-12 large-7" style="Margin: 0; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 30px; text-align: left; width: 51.33333%;">
            <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: normal; line-height: 25px; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;"> <strong>Order Number</strong><br> {{$order->so_id}} </p>
            <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: normal; line-height: 25px; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;"> <strong>Customer Name</strong><br> {{$order->contacts[0]->contact_name}}
            </p>
            <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: normal; line-height: 25px; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;"> <strong>Amount</strong><br> <b>Rp. {{number_format($order->grand_total)}}</b> </p>
          </th>
          <th class="small-12 large-5 text-center" style="Margin: 0; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 30px; text-align: center; width: 49.66667%;">
            <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: normal; line-height: 25px; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;"> <strong>Date</strong><br> {{ date('d F Y',strtotime($order->created_at)) }} </p>
            <p style="Margin: 0; Margin-bottom: 10px; color: #2b2b2b; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: normal; line-height: 25px; margin: 0; margin-bottom: 10px; padding: 0; text-align: left;"> <strong>Status</strong><br> <span style="color:#EA6564">{{ucwords($order->status_label)}}</span> </p>
          </th>
        </tr>
      </tbody>
    </table>
    @if(!empty($data['button_txt']))
      <table class="button rounded expanded" style="Margin: 0 0 16px 0; border-collapse: collapse; border-spacing: 0; margin: 0 0 16px 0; padding: 0; text-align: left; vertical-align: top; width: 100% !important;">
        <tbody>
          <tr style="padding: 0; text-align: left; vertical-align: top;">
            <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #0a0a0a; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
              <table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
                <tbody>
                  <tr style="padding: 0; text-align: left; vertical-align: top;">
                    <td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; background: #2b2b2b; border: none; border-collapse: collapse !important; border-radius: 500px; color: #fefefe; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 10px; text-align: left; vertical-align: top; word-wrap: break-word;margin-bottom:50px;">
                      <a href="{{$data['order_url']}}" align="center" class="float-center" style="Margin: 0; border: 0 solid #2199e8; border-radius: 3px; color: #fefefe; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: 600; line-height: 1.3; margin: 0; padding: 8px 16px 8px 16px; padding-left: 0; padding-right: 0; text-align: center; text-decoration: none; width: 100%;text-transform: uppercase;">{{$data['button_txt']}}</a>
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