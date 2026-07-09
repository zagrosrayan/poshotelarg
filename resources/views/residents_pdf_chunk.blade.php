<table>
    <thead>
    <tr>
        <th>نام</th>
        <th>اتاق</th>
        <th>رزرو</th>
        <th>امتیاز</th>
        <th>مجموع خرید</th>
        <th>ورود</th>
        <th>خروج</th>
        <th>موبایل</th>
        <th>موجودی</th>
    </tr>
    </thead>
    <tbody>
    @foreach($residents as $resident)
        <tr>
            <td>{{ $resident['GuestName'] }}</td>
            <td>{{ $resident['Room'] }}</td>
            <td>{{ $resident['Reserve'] }}</td>
            <td>{{ $resident['points'] }}</td>
            <td>{{ $resident['total_purchased'] }}</td>
            <td>{{ $resident['Arrival'] }}</td>
            <td>{{ $resident['departure'] }}</td>
            <td>{{ $resident['Mobile'] }}</td>
            <td>{{ $resident['balance'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>