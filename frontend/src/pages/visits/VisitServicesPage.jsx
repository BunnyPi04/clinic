import { useEffect, useMemo, useState } from "react";
import {
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import { Link, useParams } from "react-router-dom";

import apiClient from "../../api/client";
import ErrorMessage from "../../components/ErrorMessage";
import LoadingMessage from "../../components/LoadingMessage";
import useDebouncedValue from "../../hooks/useDebouncedValue";

function createCustomItem() {
  return {
    local_id: crypto.randomUUID(),
    service_name: "",
    unit_price: "",
    quantity: 1,
    display_on_patient_receipt: true,
    is_highlighted: true,
  };
}

function formatMoney(value) {
  return new Intl.NumberFormat("vi-VN").format(Number(value || 0));
}

export default function VisitServicesPage() {
  const { visitId } = useParams();
  const queryClient = useQueryClient();

  const [selectedItems, setSelectedItems] = useState({});
  const [customItems, setCustomItems] = useState([]);
  const [collectedAmount, setCollectedAmount] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("cash");

  const [serviceSearch, setServiceSearch] = useState("");
  const [selectedOrder, setSelectedOrder] = useState([]);
  const [selectedServices, setSelectedServices] = useState({});

  const debouncedServiceSearch = useDebouncedValue(
    serviceSearch,
    500
  );

  const optionsQuery = useQuery({
    queryKey: [
      "billing-options",
      visitId,
      debouncedServiceSearch,
    ],
    queryFn: async () => {
      const response = await apiClient.get(
        `/visits/${visitId}/billing-options`,
        {
          params: {
            q: debouncedServiceSearch || undefined,
          },
        }
      );

      return response.data;
    },
  });

  const visitQuery = useQuery({
    queryKey: ["visit", visitId],
    queryFn: async () => {
      const response = await apiClient.get(`/visits/${visitId}`);
      return response.data;
    },
  });

  const currentServicesQuery = useQuery({
    queryKey: ["visit-services", visitId],
    queryFn: async () => {
      const response = await apiClient.get(
        `/visits/${visitId}/services`
      );

      return response.data;
    },
  });

  const paymentQuery = useQuery({
    queryKey: ["visit-payment", visitId],
    queryFn: async () => {
      try {
        const response = await apiClient.get(
          `/visits/${visitId}/payment`
        );

        return response.data;
      } catch (error) {
        if (error.status === 404) {
          return null;
        }

        throw error;
      }
    },
  });

  const options = optionsQuery.data?.items || [];
  const currentServices = currentServicesQuery.data || [];

  useEffect(() => {
    if (!options.length || currentServicesQuery.isLoading) {
      return;
    }

    const currentCatalogServices = currentServices.filter(
      (item) => item.service_catalog_id
    );

    const currentCustomServices = currentServices.filter(
      (item) =>
        !item.service_catalog_id &&
        item.service_code !== "LAB-TOTAL"
    );

    const nextSelected = {};

    options.forEach((option) => {
      const existing = currentCatalogServices.find(
        (item) =>
          Number(item.service_catalog_id) === Number(option.id)
      );

      nextSelected[option.id] = {
        checked: Boolean(existing),
        unit_price: existing?.unit_price ?? option.price,
        quantity: existing?.quantity ?? 1,
        display_on_patient_receipt:
          existing?.display_on_patient_receipt ??
          option.display_on_patient_receipt,
      };
    });

    setSelectedItems(nextSelected);

    setCustomItems(
      currentCustomServices.map((item) => ({
        local_id: crypto.randomUUID(),
        id: item.id,
        service_name: item.service_name,
        unit_price: item.unit_price,
        quantity: item.quantity,
        display_on_patient_receipt:
          item.display_on_patient_receipt,
        is_highlighted: item.is_highlighted,
      }))
    );
  }, [
    options,
    currentServicesQuery.isLoading,
    currentServicesQuery.data,
  ]);

  const selectedTotal = useMemo(() => {
    const catalogTotal = options.reduce((total, option) => {
      const selected = selectedItems[option.id];

      if (!selected?.checked) {
        return total;
      }

      return (
        total +
        Number(selected.unit_price || 0) *
          Number(selected.quantity || 1)
      );
    }, 0);

    const customTotal = customItems.reduce(
      (total, item) =>
        total +
        Number(item.unit_price || 0) *
          Number(item.quantity || 1),
      0
    );

    const preservedLabTotal = currentServices
      .filter((item) => item.service_code === "LAB-TOTAL")
      .reduce(
        (total, item) => total + Number(item.amount || 0),
        0
      );

    return catalogTotal + customTotal + preservedLabTotal;
  }, [options, selectedItems, customItems, currentServices]);

  const saveServicesMutation = useMutation({
    mutationFn: async () => {
      const catalogItems = selectedOrder
        .map((serviceId, index) => {
          const service = selectedServices[serviceId];

          if (!service) {
            return null;
          }

          return {
            service_catalog_id: service.id,
            service_type: service.service_type,
            service_category: service.service_category,
            service_code: service.code,
            service_name: service.name,
            doctor_id:
              service.service_type === "consultation"
                ? visitQuery.data?.doctor_id
                : null,
            unit_price: Number(service.unit_price || 0),
            quantity: Number(service.quantity || 1),
            is_highlighted: Boolean(
              service.is_highlighted
            ),
            is_custom: false,
            display_on_patient_receipt: Boolean(
              service.display_on_patient_receipt
            ),
            sort_order: index + 1,
          };
        })
        .filter(Boolean);

      const customPayload = customItems
        .filter((item) => item.service_name.trim())
        .map((item, index) => ({
          service_type: "other",
          service_category: "custom",
          service_code: null,
          service_name: item.service_name.trim(),
          doctor_id: null,
          unit_price: Number(item.unit_price || 0),
          quantity: Number(item.quantity || 1),
          is_highlighted: Boolean(item.is_highlighted),
          is_custom: true,
          display_on_patient_receipt: Boolean(
            item.display_on_patient_receipt
          ),
          sort_order: options.length + index + 1,
        }));

      const response = await apiClient.put(
        `/visits/${visitId}/services/batch`,
        {
          items: [...catalogItems, ...customPayload],
        }
      );

      return response.data;
    },
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({
          queryKey: ["visit-services", visitId],
        }),
        queryClient.invalidateQueries({
          queryKey: ["visit-payment", visitId],
        }),
      ]);
    },
  });

  const createPaymentMutation = useMutation({
    mutationFn: async () => {
      const response = await apiClient.post(
        `/visits/${visitId}/payment`,
        {
          payment_method: paymentMethod,
          note: "Tạo phiếu thanh toán từ giao diện quầy",
        }
      );

      return response.data;
    },
    onSuccess: () =>
      queryClient.invalidateQueries({
        queryKey: ["visit-payment", visitId],
      }),
  });

  const collectPaymentMutation = useMutation({
    mutationFn: async () => {
      const payment = paymentQuery.data;

      if (!payment) {
        throw new Error("Chưa có phiếu thanh toán.");
      }

      const response = await apiClient.post(
        `/payments/${payment.id}/transactions`,
        {
          transaction_type: "collect",
          amount: Number(collectedAmount),
          payment_method: paymentMethod,
          note: "Thu tiền tại quầy",
        }
      );

      return response.data;
    },
    onSuccess: () => {
      setCollectedAmount("");

      queryClient.invalidateQueries({
        queryKey: ["visit-payment", visitId],
      });
    },
  });

  const selectedServicesTotal = useMemo(() => {
    return selectedOrder.reduce((total, serviceId) => {
      const service = selectedServices[serviceId];

      if (!service) {
        return total;
      }

      return (
        total +
        Number(service.unit_price || 0)
          * Number(service.quantity || 1)
      );
    }, 0);
  }, [selectedOrder, selectedServices]);

  function selectService(option) {
    if (!option.selectable) {
      return;
    }

    setSelectedServices((current) => {
      if (current[option.id]) {
        return current;
      }

      return {
        ...current,
        [option.id]: {
          ...option,
          unit_price: option.price,
          quantity: 1,
          display_on_patient_receipt:
            option.display_on_patient_receipt,
        },
      };
    });

    setSelectedOrder((current) => {
      if (current.includes(option.id)) {
        return current;
      }

      return [...current, option.id];
    });
  }

  function removeService(serviceId) {
    setSelectedServices((current) => {
      const next = { ...current };
      delete next[serviceId];
      return next;
    });

    setSelectedOrder((current) =>
      current.filter((id) => id !== serviceId)
    );
  }

  function toggleService(option) {
    if (selectedServices[option.id]) {
      removeService(option.id);
    } else {
      selectService(option);
    }
  }

  function updateSelectedService(serviceId, field, value) {
    setSelectedServices((current) => ({
      ...current,
      [serviceId]: {
        ...current[serviceId],
        [field]: value,
      },
    }));
  }

  function toggleOption(optionId) {
    setSelectedItems((current) => ({
      ...current,
      [optionId]: {
        ...current[optionId],
        checked: !current[optionId]?.checked,
      },
    }));
  }

  function updateOption(optionId, field, value) {
    setSelectedItems((current) => ({
      ...current,
      [optionId]: {
        ...current[optionId],
        [field]: value,
      },
    }));
  }

  function updateCustomItem(localId, field, value) {
    setCustomItems((current) =>
      current.map((item) =>
        item.local_id === localId
          ? { ...item, [field]: value }
          : item
      )
    );
  }

  function removeCustomItem(localId) {
    setCustomItems((current) =>
      current.filter((item) => item.local_id !== localId)
    );
  }

  if (
    visitQuery.isLoading ||
    optionsQuery.isLoading ||
    currentServicesQuery.isLoading ||
    paymentQuery.isLoading
  ) {
    return <LoadingMessage text="Đang tải phiếu dịch vụ..." />;
  }

  const error =
    visitQuery.error ||
    optionsQuery.error ||
    currentServicesQuery.error ||
    paymentQuery.error;

  if (error) {
    return <ErrorMessage error={error} />;
  }

  const visit = visitQuery.data;
  const payment = paymentQuery.data;

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Dịch vụ và thanh toán</h1>
          <p>
            {visit.patient?.full_name} — {visit.visit_code}
          </p>
        </div>

        <Link
          to={`/visits/${visitId}`}
          className="button button-secondary"
        >
          Quay lại buổi khám
        </Link>
      </div>

      {/* <div className="card">
        <h2>Danh mục dịch vụ</h2>

        <div className="billing-list">
          {options.map((option) => {
            const selected = selectedItems[option.id] || {};

            return (
              <div
                key={option.id}
                className={
                  option.is_highlighted
                    ? "billing-item billing-item-highlighted"
                    : "billing-item"
                }
              >
                <label className="billing-item-check">
                  <input
                    type="checkbox"
                    checked={Boolean(selected.checked)}
                    onChange={() => toggleOption(option.id)}
                  />

                  <span>
                    <strong>{option.name}</strong>

                    {option.description && (
                      <small>{option.description}</small>
                    )}
                  </span>
                </label>

                <div className="billing-item-fields">
                  <label>
                    Giá
                    <input
                      type="number"
                      min="0"
                      value={selected.unit_price ?? ""}
                      disabled={!selected.checked}
                      onChange={(event) =>
                        updateOption(
                          option.id,
                          "unit_price",
                          event.target.value
                        )
                      }
                    />
                  </label>

                  <label>
                    Số lượng
                    <input
                      type="number"
                      min="1"
                      value={selected.quantity ?? 1}
                      disabled={!selected.checked}
                      onChange={(event) =>
                        updateOption(
                          option.id,
                          "quantity",
                          event.target.value
                        )
                      }
                    />
                  </label>

                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={Boolean(
                        selected.display_on_patient_receipt
                      )}
                      disabled={!selected.checked}
                      onChange={(event) =>
                        updateOption(
                          option.id,
                          "display_on_patient_receipt",
                          event.target.checked
                        )
                      }
                    />

                    <span>Hiện trên phiếu bệnh nhân</span>
                  </label>
                </div>
              </div>
            );
          })}
        </div>
      </div> */}
      <div className="service-picker-grid">
        <div className="card service-picker-panel">
          <div className="service-picker-header">
            <div>
              <h2>Danh sách dịch vụ</h2>
              <p>Chọn các dịch vụ sử dụng trong buổi khám.</p>
            </div>
          </div>

          <input
            className="service-search-input"
            value={serviceSearch}
            onChange={(event) =>
              setServiceSearch(event.target.value)
            }
            placeholder="Tìm kiếm theo tên dịch vụ..."
          />

          <div className="service-picker-table-wrapper">
            <table className="service-picker-table">
              <thead>
                <tr>
                  <th></th>
                  <th>Dịch vụ</th>
                  <th>Giá</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>

              <tbody>
                {options.map((option) => {
                  const checked = Boolean(
                    selectedServices[option.id]
                  );

                  const suspended =
                    option.status === "suspended";

                  return (
                    <tr
                      key={option.id}
                      className={[
                        checked ? "service-row-selected" : "",
                        suspended ? "service-row-disabled" : "",
                      ].join(" ")}
                      onClick={() => toggleService(option)}
                    >
                      <td>
                        <input
                          type="checkbox"
                          checked={checked}
                          disabled={!option.selectable}
                          onChange={() => toggleService(option)}
                          onClick={(event) =>
                            event.stopPropagation()
                          }
                        />
                      </td>

                      <td>
                        <strong>{option.name}</strong>
                        <small>{option.code}</small>
                      </td>

                      <td>
                        {formatMoney(option.price)} VNĐ
                      </td>

                      <td>
                        <StatusBadge
                          status={option.status}
                          compact
                        />
                      </td>
                    </tr>
                  );
                })}

                {options.length === 0 && (
                  <tr>
                    <td colSpan="4" className="empty-cell">
                      Không tìm thấy dịch vụ.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        <div className="card service-picker-panel">
          <div className="service-picker-header">
            <div>
              <h2>Dịch vụ đã chọn</h2>
              <p>
                Các dịch vụ được thêm theo thứ tự lựa chọn.
              </p>
            </div>

            <strong>{selectedOrder.length} mục</strong>
          </div>

          <div className="service-picker-table-wrapper">
            <table className="service-picker-table">
              <thead>
                <tr>
                  <th>Dịch vụ</th>
                  <th>Giá</th>
                  <th>Số lượng</th>
                  <th></th>
                </tr>
              </thead>

              <tbody>
                {selectedOrder.map((serviceId) => {
                  const service = selectedServices[serviceId];

                  if (!service) {
                    return null;
                  }

                  return (
                    <tr key={serviceId}>
                      <td>
                        <strong>{service.name}</strong>
                        <small>{service.code}</small>
                      </td>

                      <td>
                        <input
                          type="number"
                          min="0"
                          value={service.unit_price}
                          onChange={(event) =>
                            updateSelectedService(
                              serviceId,
                              "unit_price",
                              event.target.value
                            )
                          }
                        />
                      </td>

                      <td>
                        <input
                          type="number"
                          min="1"
                          value={service.quantity}
                          onChange={(event) =>
                            updateSelectedService(
                              serviceId,
                              "quantity",
                              event.target.value
                            )
                          }
                        />
                      </td>

                      <td>
                        <button
                          type="button"
                          className="button button-danger"
                          onClick={() =>
                            removeService(serviceId)
                          }
                        >
                          Bỏ chọn
                        </button>
                      </td>
                    </tr>
                  );
                })}

                {selectedOrder.length === 0 && (
                  <tr>
                    <td colSpan="4" className="empty-cell">
                      Chưa chọn dịch vụ nào.
                    </td>
                  </tr>
                )}
              </tbody>

              <tfoot>
                <tr>
                  <td colSpan="2">
                    <strong>Tạm tính</strong>
                  </td>
                  <td colSpan="2" className="service-total-cell">
                    <strong>
                      {formatMoney(selectedServicesTotal)} VNĐ
                    </strong>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="section-title-actions">
          <div>
            <h2>Mục phát sinh</h2>
            <p>
              Dùng khi chưa có dịch vụ trong danh mục hoặc phát sinh
              bất ngờ.
            </p>
          </div>

          <button
            type="button"
            className="button"
            onClick={() =>
              setCustomItems((current) => [
                ...current,
                createCustomItem(),
              ])
            }
          >
            Thêm mục khác
          </button>
        </div>

        {customItems.map((item) => (
          <div key={item.local_id} className="custom-billing-row">
            <label>
              Tên dịch vụ
              <input
                value={item.service_name}
                onChange={(event) =>
                  updateCustomItem(
                    item.local_id,
                    "service_name",
                    event.target.value
                  )
                }
              />
            </label>

            <label>
              Giá
              <input
                type="number"
                min="0"
                value={item.unit_price}
                onChange={(event) =>
                  updateCustomItem(
                    item.local_id,
                    "unit_price",
                    event.target.value
                  )
                }
              />
            </label>

            <label>
              Số lượng
              <input
                type="number"
                min="1"
                value={item.quantity}
                onChange={(event) =>
                  updateCustomItem(
                    item.local_id,
                    "quantity",
                    event.target.value
                  )
                }
              />
            </label>

            <label className="checkbox-label">
              <input
                type="checkbox"
                checked={item.display_on_patient_receipt}
                onChange={(event) =>
                  updateCustomItem(
                    item.local_id,
                    "display_on_patient_receipt",
                    event.target.checked
                  )
                }
              />

              <span>Hiện trên phiếu bệnh nhân</span>
            </label>

            <button
              type="button"
              className="button button-danger"
              onClick={() => removeCustomItem(item.local_id)}
            >
              Xóa
            </button>
          </div>
        ))}

        {customItems.length === 0 && (
          <p className="muted-text">Chưa có mục phát sinh.</p>
        )}
      </div>

      <div className="card billing-summary">
        <div>
          <span>Tổng chi phí dự kiến</span>
          <strong>{formatMoney(selectedTotal)} VNĐ</strong>
        </div>

        <button
          type="button"
          className="button button-primary"
          disabled={saveServicesMutation.isPending}
          onClick={() => saveServicesMutation.mutate()}
        >
          {saveServicesMutation.isPending
            ? "Đang lưu..."
            : "Lưu danh sách dịch vụ"}
        </button>
      </div>

      {saveServicesMutation.isError && (
        <ErrorMessage error={saveServicesMutation.error} />
      )}

      {saveServicesMutation.isSuccess && (
        <div className="success-box">
          Đã lưu danh sách dịch vụ.
        </div>
      )}

      <div className="card">
        <h2>Thanh toán</h2>

        {!payment && (
          <div className="payment-empty">
            <p>
              Chưa có phiếu thanh toán. Hãy lưu danh sách dịch vụ
              trước khi tạo phiếu.
            </p>

            <button
              type="button"
              className="button button-primary"
              disabled={createPaymentMutation.isPending}
              onClick={() => createPaymentMutation.mutate()}
            >
              {createPaymentMutation.isPending
                ? "Đang tạo..."
                : "Tạo phiếu thanh toán"}
            </button>
          </div>
        )}

        {payment && (
          <>
            <dl className="detail-list">
              <dt>Số phiếu</dt>
              <dd>{payment.receipt_no}</dd>

              <dt>Tổng tiền</dt>
              <dd>{formatMoney(payment.total_amount)} VNĐ</dd>

              <dt>Đã thu</dt>
              <dd>{formatMoney(payment.amount_paid)} VNĐ</dd>

              <dt>Còn lại</dt>
              <dd>
                {formatMoney(
                  Math.max(
                    Number(payment.total_amount) -
                      Number(payment.amount_paid),
                    0
                  )
                )}{" "}
                VNĐ
              </dd>

              <dt>Trạng thái</dt>
              <dd>
                {payment.payment_status === "paid"
                  ? "Đã thu đủ"
                  : payment.payment_status === "partial"
                    ? "Thu một phần"
                    : "Chờ thu"}
              </dd>
            </dl>

            <div className="payment-collection-form">
              <label>
                Số tiền thu
                <input
                  type="number"
                  min="1"
                  value={collectedAmount}
                  onChange={(event) =>
                    setCollectedAmount(event.target.value)
                  }
                />
              </label>

              <label>
                Phương thức
                <select
                  value={paymentMethod}
                  onChange={(event) =>
                    setPaymentMethod(event.target.value)
                  }
                >
                  <option value="cash">Tiền mặt</option>
                  <option value="transfer">Chuyển khoản</option>
                  <option value="mixed">Kết hợp</option>
                </select>
              </label>

              <button
                type="button"
                className="button button-primary"
                disabled={
                  collectPaymentMutation.isPending ||
                  Number(collectedAmount) <= 0
                }
                onClick={() => collectPaymentMutation.mutate()}
              >
                {collectPaymentMutation.isPending
                  ? "Đang thu..."
                  : "Xác nhận thu tiền"}
              </button>
            </div>
          </>
        )}

        {createPaymentMutation.isError && (
          <ErrorMessage error={createPaymentMutation.error} />
        )}

        {collectPaymentMutation.isError && (
          <ErrorMessage error={collectPaymentMutation.error} />
        )}
      </div>
    </section>
  );
}
