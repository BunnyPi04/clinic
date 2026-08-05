import { useEffect, useState } from "react";
import {
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";

import apiClient from "../../api/client";
import CurrencyInput from "../../components/CurrencyInput";
import ErrorMessage from "../../components/ErrorMessage";
import LoadingMessage from "../../components/LoadingMessage";
import StatusBadge from "../../components/StatusBadge";
import StatusFilterDropdown from "../../components/StatusFilterDropdown";

const CURRENCY = import.meta.env.VITE_CURRENCY || "VNĐ";

const STATUS_LABELS = {
  active: "Đang hoạt động",
  suspended: "Tạm ngưng",
  discontinued: "Ngừng vĩnh viễn",
};

const TYPE_LABELS = {
  consultation: "Phí khám",
  lab_test: "Xét nghiệm",
  ultrasound: "Siêu âm",
  external_test: "Xét nghiệm ngoài",
  other: "Dịch vụ khác",
};

const SERVICE_CODE_PREFIXES = {
  CS: {
    label: "Phí khám",
    description: "Consultation",
    serviceType: "consultation",
    serviceCategory: "exam",
  },
  BT: {
    label: "Xét nghiệm hóa sinh",
    description: "Biochemical test",
    serviceType: "lab_test",
    serviceCategory: "biochemistry",
  },
  UT: {
    label: "Xét nghiệm nước tiểu",
    description: "Urine test",
    serviceType: "lab_test",
    serviceCategory: "urine",
  },
  BL: {
    label: "Xét nghiệm máu",
    description: "Blood test",
    serviceType: "lab_test",
    serviceCategory: "hematology",
  },
  US: {
    label: "Siêu âm",
    description: "Ultrasound",
    serviceType: "ultrasound",
    serviceCategory: "imaging",
  },
  ET: {
    label: "Xét nghiệm bên ngoài",
    description: "External test",
    serviceType: "external_test",
    serviceCategory: "external",
  },
  OT: {
    label: "Khác",
    description: "Other",
    serviceType: "other",
    serviceCategory: "other",
  },
};

function createEmptyForm() {
  return {
    id: null,
    code_prefix: "OT",
    code: "",
    name: "",
    service_type: "other",
    service_category: "other",
    default_price: 0,
    status: "active",
    is_highlighted_default: false,
    display_on_patient_receipt_default: true,
    sort_order: 0,
    description: "",
  };
}

function useDebouncedValue(value, delay = 500) {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const timeoutId = window.setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => window.clearTimeout(timeoutId);
  }, [value, delay]);

  return debouncedValue;
}

function formatMoney(value) {
  return new Intl.NumberFormat("en-US", {
    maximumFractionDigits: 0,
  }).format(Number(value || 0));
}

export default function ServiceCatalogPage() {
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [form, setForm] = useState(createEmptyForm);
  const [isFormOpen, setIsFormOpen] = useState(false);

  const debouncedSearch = useDebouncedValue(search, 500);

  const servicesQuery = useQuery({
    queryKey: [
      "service-catalogs",
      debouncedSearch,
      statusFilter,
    ],
    queryFn: async () => {
      const response = await apiClient.get("/service-catalogs", {
        params: {
          q: debouncedSearch || undefined,
          status: statusFilter || undefined,
        },
      });

      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (payload) => {
      if (payload.id) {
        const response = await apiClient.put(
          `/service-catalogs/${payload.id}`,
          payload
        );

        return response.data;
      }

      const response = await apiClient.post(
        "/service-catalogs",
        payload
      );

      return response.data;
    },
    onSuccess: () => {
      setForm(createEmptyForm());
      setIsFormOpen(false);

      queryClient.invalidateQueries({
        queryKey: ["service-catalogs"],
      });
    },
  });

  const services = servicesQuery.data?.data || [];

  function updateField(event) {
    const { name, value, type, checked } = event.target;

    setForm((current) => ({
      ...current,
      [name]: type === "checkbox" ? checked : value,
    }));
  }

  function handleCodePrefixChange(event) {
    const prefix = event.target.value;
    const config = SERVICE_CODE_PREFIXES[prefix];

    setForm((current) => ({
      ...current,
      code_prefix: prefix,
      service_type:
        config?.serviceType || current.service_type,
      service_category:
        config?.serviceCategory || current.service_category,
    }));
  }

  function getPrefixFromServiceCode(code) {
    const prefix = String(code || "")
      .slice(0, 2)
      .toUpperCase();

    return SERVICE_CODE_PREFIXES[prefix]
      ? prefix
      : "OT";
  }

  function editService(service) {
    setForm({
      id: service.id,
      code: service.code,
      code_prefix: getPrefixFromServiceCode(service.code),
      name: service.name,
      service_type: service.service_type,
      service_category: service.service_category || "",
      default_price: service.default_price || 0,
      status: service.status,
      is_highlighted_default:
        Boolean(service.is_highlighted_default),
      display_on_patient_receipt_default:
        Boolean(service.display_on_patient_receipt_default),
      sort_order: service.sort_order || 0,
      description: service.description || "",
    });

    setIsFormOpen(true);

    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  }

  function toggleCreateForm() {
    if (isFormOpen) {
      setForm(createEmptyForm());
      setIsFormOpen(false);
      return;
    }

    setForm(createEmptyForm());
    setIsFormOpen(true);
  }

  function closeForm() {
    setForm(createEmptyForm());
    setIsFormOpen(false);
  }

  function submit(event) {
    event.preventDefault();

    const payload = {
      ...form,
      default_price: Number(form.default_price || 0),
      sort_order: Number(form.sort_order || 0),
      is_highlighted_default:
        Boolean(form.is_highlighted_default),
      display_on_patient_receipt_default:
        Boolean(form.display_on_patient_receipt_default),
    };

    /*
    * Backend tự tạo code khi thêm mới.
    */
    delete payload.code;

    if (form.id) {
      delete payload.code_prefix;
    }

    saveMutation.mutate(payload);
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Danh mục dịch vụ khám</h1>

          <p>
            Quản lý các dịch vụ, giá mặc định và trạng thái cung cấp.
          </p>
        </div>

        <button
          type="button"
          className={
            isFormOpen
              ? "button button-secondary"
              : "button button-primary"
          }
          onClick={toggleCreateForm}
        >
          {isFormOpen
            ? "Ẩn form"
            : "Thêm dịch vụ mới"}
        </button>
      </div>

      {isFormOpen && (
        <form
          className="card form-grid service-catalog-form"
          onSubmit={submit}
        >
          <div className="service-form-status-row form-field-wide">
            <div>
              <span className="service-form-status-label">
                Trạng thái hiện tại
              </span>

              <StatusBadge status={form.status} />
            </div>

            <span className="service-form-mode">
              {form.id
                ? `Đang sửa dịch vụ #${form.id}`
                : "Đang tạo dịch vụ mới"}
            </span>
          </div>

          <div className="form-field form-field-wide">
            <label>Mã dịch vụ</label>

            <div className="service-code-builder">
              <select
                name="code_prefix"
                value={form.code_prefix}
                onChange={handleCodePrefixChange}
                disabled={Boolean(form.id)}
              >
                {Object.entries(SERVICE_CODE_PREFIXES).map(
                  ([prefix, config]) => (
                    <option key={prefix} value={prefix}>
                      {prefix} — {config.label}
                    </option>
                  )
                )}
              </select>

              <input
                type="text"
                value={form.id ? form.code : "Tự động"}
                disabled
                aria-label="Mã dịch vụ tự động"
              />
            </div>

            {!form.id && (
              <small className="form-help-text">
                Mã dịch vụ sẽ gồm tiền tố đã chọn và 10 chữ số tăng
                dần, ví dụ: {form.code_prefix}0000000001.
              </small>
            )}

            <div className="service-code-preview">
              <span>Mã dự kiến:</span>

              <code>
                {form.id
                  ? form.code
                  : ''}
              </code>
            </div>

            {!form.id && (
              <small className="form-help-text">
                Backend sẽ kiểm tra trùng và tự sinh lại chuỗi nếu cần.
              </small>
            )}
          </div>

          <div className="form-field">
            <label>Tên dịch vụ *</label>

            <input
              name="name"
              value={form.name}
              onChange={updateField}
              required
            />
          </div>

          <div className="form-field">
            <label>Loại dịch vụ *</label>

            <select
              name="service_type"
              value={form.service_type}
              onChange={updateField}
            >
              {Object.entries(TYPE_LABELS).map(
                ([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                )
              )}
            </select>
          </div>

          <div className="form-field">
            <label>Nhóm dịch vụ</label>

            <input
              name="service_category"
              value={form.service_category}
              onChange={updateField}
              placeholder="Ví dụ: biochemistry"
            />
          </div>

          <div className="form-field">
            <label>Giá mặc định</label>

            <CurrencyInput
              name="default_price"
              value={form.default_price}
              onChange={updateField}
              currency={CURRENCY}
              placeholder="0"
            />
          </div>

          <div className="form-field">
            <label>Trạng thái</label>

            <select
              name="status"
              value={form.status}
              onChange={updateField}
            >
              {Object.entries(STATUS_LABELS).map(
                ([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                )
              )}
            </select>
          </div>

          <div className="form-field">
            <label>Thứ tự hiển thị</label>

            <input
              type="number"
              min="0"
              name="sort_order"
              value={form.sort_order}
              onChange={updateField}
            />
          </div>

          <div className="form-field">
            <label className="checkbox-label">
              <input
                type="checkbox"
                name="is_highlighted_default"
                checked={form.is_highlighted_default}
                onChange={updateField}
              />

              <span>Highlight mặc định</span>
            </label>

            <label className="checkbox-label">
              <input
                type="checkbox"
                name="display_on_patient_receipt_default"
                checked={
                  form.display_on_patient_receipt_default
                }
                onChange={updateField}
              />

              <span>Hiện trên phiếu bệnh nhân</span>
            </label>
          </div>

          <div className="form-field form-field-wide">
            <label>Mô tả</label>

            <textarea
              name="description"
              value={form.description}
              onChange={updateField}
            />
          </div>

          <div className="form-actions form-field-wide">
            <button
              type="submit"
              className="button button-primary"
              disabled={saveMutation.isPending}
            >
              {saveMutation.isPending
                ? "Đang lưu..."
                : form.id
                  ? "Cập nhật dịch vụ"
                  : "Thêm dịch vụ"}
            </button>

            <button
              type="button"
              className="button button-secondary"
              onClick={closeForm}
            >
              {form.id
                ? "Hủy chỉnh sửa"
                : "Đóng"}
            </button>
          </div>
        </form>
      )}

      {saveMutation.isError && (
        <ErrorMessage error={saveMutation.error} />
      )}

      <div className="service-toolbar">
        <input
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder="Tìm theo tên hoặc mã dịch vụ..."
        />

        <StatusFilterDropdown
          value={statusFilter}
          onChange={setStatusFilter}
        />
      </div>

      {servicesQuery.isLoading && <LoadingMessage />}

      {servicesQuery.isError && (
        <ErrorMessage error={servicesQuery.error} />
      )}

      {!servicesQuery.isLoading &&
        !servicesQuery.isError && (
          <div className="table-card">
            <table>
              <thead>
                <tr>
                  <th>Mã</th>
                  <th>Tên dịch vụ</th>
                  <th>Loại</th>
                  <th>Giá</th>
                  <th>Trạng thái</th>
                  <th></th>
                </tr>
              </thead>

              <tbody>
                {services.map((service) => (
                  <tr
                    key={service.id}
                    className={
                      `service-status-row ` +
                      `service-status-row-${service.status}`
                    }
                  >
                    <td>{service.code}</td>

                    <td>{service.name}</td>

                    <td>
                      {TYPE_LABELS[service.service_type] ||
                        service.service_type}
                    </td>

                    <td>
                      {formatMoney(service.default_price)}{" "}
                      {CURRENCY}
                    </td>

                    <td>
                      <StatusBadge status={service.status} />
                    </td>

                    <td>
                      <button
                        type="button"
                        className="button button-secondary"
                        onClick={() => editService(service)}
                      >
                        Sửa
                      </button>
                    </td>
                  </tr>
                ))}

                {services.length === 0 && (
                  <tr>
                    <td colSpan="6" className="empty-cell">
                      Không tìm thấy dịch vụ.
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
