export default function ErrorMessage({ error }) {
  if (!error) {
    return null;
  }

  return (
    <div className="error-box">
      {error.message || "Có lỗi xảy ra khi tải dữ liệu."}
    </div>
  );
}
