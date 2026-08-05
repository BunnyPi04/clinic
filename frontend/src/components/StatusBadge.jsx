const statusConfig = {
  active: {
    label: "Đang hoạt động",
    className: "status-active",
  },
  suspended: {
    label: "Tạm ngưng",
    className: "status-suspended",
  },
  discontinued: {
    label: "Ngừng vĩnh viễn",
    className: "status-discontinued",
  },
};

export default function StatusBadge({
  status,
  showDot = true,
  compact = false,
}) {
  const config = statusConfig[status] || {
    label: status || "Không xác định",
    className: "status-unknown",
  };

  return (
    <span
      className={[
        "status-pill",
        config.className,
        compact ? "status-pill-compact" : "",
      ]
        .filter(Boolean)
        .join(" ")}
    >
      {showDot && <span className="status-dot" aria-hidden="true" />}

      <span className="status-pill-text">{config.label}</span>
    </span>
  );
}

export { statusConfig };
