import { useEffect, useMemo, useRef, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";

import apiClient from "../api/client";
import ErrorMessage from "../components/ErrorMessage";
import LoadingMessage from "../components/LoadingMessage";

const statusLabels = {
  registered: "Đã tiếp nhận",
  awaiting_payment: "Chờ thu tiền",
  partial_paid: "Thu một phần",
  paid: "Đã thu đủ",
  collecting_documents: "Đang bổ sung hồ sơ",
  reviewing: "Đang kiểm tra",
  doctor_ready: "Sẵn sàng khám",
  completed: "Hoàn tất",
  cancelled: "Đã hủy",
};

const priorityLabels = {
  elderly: "Người cao tuổi",
  weak: "Bệnh nhân yếu",
  emergency: "Cấp cứu",
  other: "Khác",
};

const vietnameseWeekdays = [
  "Chủ nhật",
  "Thứ hai",
  "Thứ ba",
  "Thứ tư",
  "Thứ năm",
  "Thứ sáu",
  "Thứ bảy",
];

function getLocalDateValue(date = new Date()) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function parseDateOnly(value) {
  if (!value) {
    return null;
  }

  const [year, month, day] = value.split("-").map(Number);

  return new Date(year, month - 1, day);
}

function formatDate(value) {
  const date = parseDateOnly(value);

  if (!date || Number.isNaN(date.getTime())) {
    return "—";
  }

  return new Intl.DateTimeFormat("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

function parseVisitDateTime(visit) {
  const value =
    visit.visit_datetime ||
    visit.scheduled_at ||
    visit.created_at ||
    visit.visit_date;

  if (!value) {
    return null;
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return parseDateOnly(value);
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? null : date;
}

function formatVisitDate(visit) {
  const date = parseVisitDateTime(visit);

  if (!date) {
    return "—";
  }

  return new Intl.DateTimeFormat("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date);
}

function formatVisitTime(visit) {
  const date = parseVisitDateTime(visit);

  if (!date) {
    return "—";
  }

  /*
   * visit_date chỉ chứa ngày, không có giờ.
   */
  const rawValue =
    visit.visit_datetime ||
    visit.scheduled_at ||
    visit.created_at;

  if (!rawValue) {
    return "Chưa có giờ";
  }

  return new Intl.DateTimeFormat("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  }).format(date);
}

function CalendarIcon() {
  return (
    <svg
      viewBox="0 0 24 24"
      width="18"
      height="18"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <rect x="3" y="5" width="18" height="16" rx="2" />
      <path d="M16 3v4M8 3v4M3 10h18" />
    </svg>
  );
}

export default function VisitsPage() {
  const today = useMemo(() => getLocalDateValue(), []);

  /*
   * Mặc định hiển thị lịch khám hôm nay.
   */
  const [dateFrom, setDateFrom] = useState(today);
  const [dateTo, setDateTo] = useState(today);

  const [draftDateFrom, setDraftDateFrom] = useState(today);
  const [draftDateTo, setDraftDateTo] = useState(today);

  const [isDatePickerOpen, setIsDatePickerOpen] = useState(false);

  const datePickerRef = useRef(null);

  const isTodayActive =
    dateFrom === today && dateTo === today;

  const hasDateFilter = Boolean(dateFrom || dateTo);

  const visitsQuery = useQuery({
    queryKey: ["visits", dateFrom, dateTo],
    queryFn: async () => {
      const response = await apiClient.get("/visits", {
        params: {
          date_from: dateFrom || undefined,
          date_to: dateTo || undefined,
        },
      });

      return response.data;
    },
  });

  const visits = visitsQuery.data?.data || [];

  const tableTitle = useMemo(() => {
    if (isTodayActive) {
      const currentDate = parseDateOnly(today);
      const weekday = vietnameseWeekdays[currentDate.getDay()];

      return `Lịch khám ${weekday} - ${formatDate(today)}`;
    }

    if (!hasDateFilter) {
      return "Tất cả lịch khám";
    }

    if (dateFrom && dateTo) {
      if (dateFrom === dateTo) {
        return `Lịch khám ngày ${formatDate(dateFrom)}`;
      }

      return `${formatDate(dateFrom)} - ${formatDate(dateTo)}`;
    }

    if (dateFrom) {
      return `Lịch khám từ ${formatDate(dateFrom)}`;
    }

    return `Lịch khám đến ${formatDate(dateTo)}`;
  }, [
    dateFrom,
    dateTo,
    hasDateFilter,
    isTodayActive,
    today,
  ]);

  useEffect(() => {
    function handleOutsideClick(event) {
      if (
        datePickerRef.current &&
        !datePickerRef.current.contains(event.target)
      ) {
        setIsDatePickerOpen(false);
      }
    }

    document.addEventListener("mousedown", handleOutsideClick);

    return () => {
      document.removeEventListener("mousedown", handleOutsideClick);
    };
  }, []);

  function showToday() {
    setDateFrom(today);
    setDateTo(today);
    setDraftDateFrom(today);
    setDraftDateTo(today);
    setIsDatePickerOpen(false);
  }

  function toggleDatePicker() {
    setDraftDateFrom(dateFrom);
    setDraftDateTo(dateTo);
    setIsDatePickerOpen((current) => !current);
  }

  function applyDateRange() {
    if (
      draftDateFrom &&
      draftDateTo &&
      draftDateFrom > draftDateTo
    ) {
      return;
    }

    setDateFrom(draftDateFrom);
    setDateTo(draftDateTo);
    setIsDatePickerOpen(false);
  }

  function showAllVisits() {
    setDateFrom("");
    setDateTo("");
    setDraftDateFrom("");
    setDraftDateTo("");
    setIsDatePickerOpen(false);
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Danh sách buổi khám</h1>

          <p>
            Theo dõi các buổi khám đã được tạo tại quầy.
          </p>
        </div>

        <Link
          to="/visits/create"
          className="button button-primary"
        >
          Tạo buổi khám
        </Link>
      </div>

      <div className="visit-filter-toolbar">
        <div className="visit-date-filter-buttons">
          <button
            type="button"
            className={[
              "button",
              "visit-today-button",
              isTodayActive
                ? "visit-today-button-active"
                : "visit-today-button-inactive",
            ].join(" ")}
            onClick={showToday}
          >
            Lịch khám hôm nay
          </button>

          <div
            className="visit-date-picker"
            ref={datePickerRef}
          >
            <button
              type="button"
              className={[
                "button",
                "visit-date-picker-trigger",
                isDatePickerOpen
                  ? "visit-date-picker-trigger-active"
                  : "",
              ].join(" ")}
              onClick={toggleDatePicker}
            >
              <CalendarIcon />
              <span>Chọn ngày</span>
            </button>

            {isDatePickerOpen && (
              <div className="visit-date-picker-popover">
                <div className="visit-date-picker-heading">
                  <strong>Chọn khoảng ngày</strong>

                  <button
                    type="button"
                    className="visit-date-picker-close"
                    onClick={() =>
                      setIsDatePickerOpen(false)
                    }
                    aria-label="Đóng"
                  >
                    ×
                  </button>
                </div>

                <div className="visit-date-picker-fields">
                  <label>
                    Từ ngày

                    <input
                      type="date"
                      value={draftDateFrom}
                      max={draftDateTo || undefined}
                      onChange={(event) =>
                        setDraftDateFrom(event.target.value)
                      }
                    />
                  </label>

                  <label>
                    Đến ngày

                    <input
                      type="date"
                      value={draftDateTo}
                      min={draftDateFrom || undefined}
                      onChange={(event) =>
                        setDraftDateTo(event.target.value)
                      }
                    />
                  </label>
                </div>

                {draftDateFrom &&
                  draftDateTo &&
                  draftDateFrom > draftDateTo && (
                    <div className="field-error">
                      Ngày bắt đầu không được sau ngày kết thúc.
                    </div>
                  )}

                <div className="visit-date-picker-actions">
                  <button
                    type="button"
                    className="button button-secondary"
                    onClick={showAllVisits}
                  >
                    Tất cả lịch khám
                  </button>

                  <button
                    type="button"
                    className="button button-primary"
                    disabled={
                      Boolean(
                        draftDateFrom &&
                          draftDateTo &&
                          draftDateFrom > draftDateTo
                      )
                    }
                    onClick={applyDateRange}
                  >
                    Áp dụng
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>

        {!isTodayActive && hasDateFilter && (
          <button
            type="button"
            className="visit-filter-summary"
            onClick={toggleDatePicker}
          >
            <CalendarIcon />

            <span>
              {dateFrom && dateTo
                ? dateFrom === dateTo
                  ? formatDate(dateFrom)
                  : `${formatDate(dateFrom)} - ${formatDate(dateTo)}`
                : dateFrom
                  ? `Từ ${formatDate(dateFrom)}`
                  : `Đến ${formatDate(dateTo)}`}
            </span>
          </button>
        )}

        {!hasDateFilter && (
          <span className="visit-filter-all-label">
            Đang hiển thị tất cả lịch khám
          </span>
        )}
      </div>

      <div className="visit-list-title-row">
        <div>
          <h2>{tableTitle}</h2>

          {!visitsQuery.isLoading &&
            !visitsQuery.isError && (
              <span>
                {visits.length} buổi khám
              </span>
            )}
        </div>
      </div>

      {visitsQuery.isLoading && <LoadingMessage />}

      {visitsQuery.isError && (
        <ErrorMessage error={visitsQuery.error} />
      )}

      {!visitsQuery.isLoading &&
        !visitsQuery.isError && (
          <div className="table-card">
            <table>
              <thead>
                <tr>
                  <th>
                    {isTodayActive
                      ? "Giờ"
                      : "Ngày - Giờ"}
                  </th>
                  <th>Số thứ tự</th>
                  <th>Bệnh nhân</th>
                  <th>Bác sĩ</th>
                  <th>Loại khám</th>
                  <th>Trạng thái</th>
                  <th>Ưu tiên</th>
                  <th></th>
                </tr>
              </thead>

              <tbody>
                {visits.map((visit) => (
                  <tr
                    key={visit.id}
                    className={
                      visit.priority_flag
                        ? "priority-row"
                        : ""
                    }
                  >
                    <td>
                      {isTodayActive ? (
                        <div className="visit-time-only">
                          {formatVisitTime(visit)}
                        </div>
                      ) : (
                        <div className="visit-date-time">
                          <span className="visit-date-part">
                            {formatVisitDate(visit)}
                          </span>

                          <span className="visit-time-part">
                            {formatVisitTime(visit)}
                          </span>
                        </div>
                      )}
                    </td>

                    <td>
                      <strong>
                        {visit.queue_number || "—"}
                      </strong>
                    </td>

                    <td>
                      {visit.patient?.full_name || "—"}
                    </td>

                    <td>
                      {visit.doctor?.full_name || "—"}
                    </td>

                    <td>
                      {visit.visit_type === "first_visit"
                        ? "Lần đầu"
                        : "Tái khám"}
                    </td>

                    <td>
                      {statusLabels[visit.status] ||
                        visit.status}
                    </td>

                    <td>
                      {visit.priority_flag
                        ? priorityLabels[
                            visit.priority_type
                          ] ||
                          visit.priority_type ||
                          "Có"
                        : "Không"}
                    </td>

                    <td>
                      <Link to={`/visits/${visit.id}`}>
                        Xem
                      </Link>
                    </td>
                  </tr>
                ))}

                {visits.length === 0 && (
                  <tr>
                    <td
                      colSpan="8"
                      className="empty-cell"
                    >
                      Không có lịch khám trong khoảng thời gian này.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
    </section>
  );
}
