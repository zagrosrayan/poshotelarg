<table>
    <thead>
    <tr>
        <th>شناسه</th>
        <th>نام</th>
        <th>کد</th>
        <th>مقدار</th>
        <th>استفاده</th>
        <th>مشتری</th>
        <th>رزرو</th>
        <th>تاریخ</th>
    </tr>
    </thead>
    <tbody>
    @foreach($discounts as $discount)
        <tr>
            <td>{{ $discount['id'] }}</td>
            <td>{{ $discount['name'] }}</td>
            <td>{{ $discount['code'] }}</td>
            <td>{{ $discount['discount_value'] }}</td>
            <td>{{ $discount['usage'] }}</td>
            <td>{{ $discount['customer'] }}</td>
            <td>{{ $discount['reserve_number'] }}</td>
            <td>{{ $discount['created_at'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>