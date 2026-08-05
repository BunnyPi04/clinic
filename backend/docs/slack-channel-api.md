# API tạo Slack channel và invite thành viên

## 1. Cấu hình Slack app

Trong trang quản lý Slack app, vào **OAuth & Permissions** và thêm Bot Token
Scopes:

- Channel public: `channels:manage`
- Channel private: `groups:write`
- Invite vào channel public: `channels:write.invites`
- Invite vào channel private: `groups:write.invites`

Sau khi đổi scope, chọn **Reinstall to Workspace**, rồi sao chép
**Bot User OAuth Token** (`xoxb-...`) vào `backend/.env`:

```dotenv
SLACK_BOT_USER_OAUTH_TOKEN=xoxb-your-token
SLACK_CHANNEL_NAME_PREFIX=clinic-
```

Không commit token thật lên Git.

Nếu ứng dụng đang cache config:

```bash
cd backend
php artisan config:clear
```

## 2. Lấy Workspace ID

Cách chắc chắn nhất là gọi `auth.test` bằng chính bot token:

```bash
curl --request POST https://slack.com/api/auth.test \
  --header "Authorization: Bearer xoxb-your-token"
```

Giá trị `team_id` trong response (ví dụ `T12345678`) chính là Workspace ID.
Slack cũng cho phép xem ID từ mục thông tin workspace hoặc dùng `team.info`.

Lưu ý: `team_id` trong `conversations.create` chỉ chọn workspace khi dùng
org-level token trên Enterprise Grid. Với workspace-level bot token, Slack bỏ qua
tham số này và luôn dùng workspace nơi token đã được cài.

## 3. Test bằng Postman

Tạo request:

- Method: `POST`
- URL: `http://localhost:8020/api/bot-notifications/channels`
- Header: `Accept: application/json`
- Header: `Content-Type: application/json`
- Body → raw → JSON:

```json
{
  "workspace_id": "T123456789",
  "channel_name": "visit-20260731-001",
  "member_ids": [
    "U123456789",
    "U987654321"
  ],
  "is_private": false
}
```

Với prefix `clinic-`, channel thực tế sẽ là
`clinic-visit-20260731-001`.

Response thành công:

```json
{
  "message": "Tạo Slack channel và invite thành viên thành công.",
  "data": {
    "workspace_id": "T123456789",
    "channel": {
      "id": "C123456789",
      "name": "clinic-visit-20260731-001"
    },
    "invited_member_ids": [
      "U123456789",
      "U987654321"
    ]
  }
}
```

Để lấy Slack member ID: mở profile thành viên trong Slack, chọn menu ba chấm,
rồi chọn **Copy member ID**.

`member_ids` có thể là mảng rỗng nếu chỉ muốn tạo channel. Tối đa 1.000 ID,
không được trùng nhau. Tên channel chỉ gồm chữ thường, số, `-`, `_`; tổng độ
dài sau khi ghép prefix không quá 80 ký tự.

Nếu invite thất bại sau khi channel đã được tạo, response có
`channel_created: true` và thông tin `channel`; không gửi lại request tạo channel
với cùng tên. Hãy sửa scope/member ID rồi invite tiếp vào channel ID đã trả về.
