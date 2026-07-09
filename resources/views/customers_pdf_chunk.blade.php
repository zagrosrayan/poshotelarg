<table>
    <thead>
    <tr>
        <th>شناسه</th>
        <th>نام</th>
        <th>تلفن</th>
        <th>امتیاز</th>
        <th>تعداد سفارشات</th>
        <th>مجموع</th>
        <th>آخرین سفارش</th>
    </tr>
    </thead>
    <tbody>
    @foreach($customers as $customer)
        <tr>
            <td>{{ $customer['id'] }}</td>
            <td>{{ $customer['name'] }}</td>
            <td>{{ $customer['phone'] }}</td>
            <td>{{ $customer['points'] }}</td>
            <td>{{ $customer['completeOrderCount'] }}</td>
            <td>{{ $customer['completeOrderTotal'] }}</td>
            <td>{{ $customer['lastOrderDate'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>